<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceSheetExport;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\employee;
use App\Support\Attendance\KioskRecorder;
use App\Support\Attendance\MonthOverview;
use App\Support\Attendance\MonthSheet;
use App\Support\Attendance\Roster;
use App\Support\Attendance\ScanRecorder;
use App\Support\Attendance\ShiftClock;
use App\Support\Attendance\ShiftPlans;
use App\Support\Attendance\ShiftReasons;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Operations -> Attendance.
 *
 * Who can use it: anyone who can see the client in their field (head office roles
 * see every client), same as the client list. Everything here is scoped through
 * Roster::clientsFor(), so a branch can never read or mark another branch's client.
 */
class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ---------------------------------------------------------------- overview

    public function index(Request $request)
    {
        $month = $this->month($request);
        $clients = Roster::clientsFor(Auth::user())
            ->with('field:id,name')
            ->orderByRaw('COALESCE(business_name, name)')
            ->get();

        $rows = MonthOverview::rows($clients, $month);
        $pending = Attendance::where('status', Attendance::PENDING)->whereNull('overtime_id')
            ->whereIn('client_id', $clients->pluck('id')->all() ?: [0])->count();

        return view('attendance.index', compact('rows', 'month', 'pending'));
    }

    // ---------------------------------------------------------------- one client's month

    public function show(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $month = $this->month($request);
        $sheet = new MonthSheet($client, $month);
        $plans = $client->shiftPlans()->with('user:id,name')->get();
        $newPin = session('new_pin');
        // Clients a guard can be moved to for a shift (the ones this user takes attendance for).
        $moveTargets = Roster::clientsFor(Auth::user())->whereKeyNot($client->id)
            ->orderByRaw('COALESCE(business_name, name)')->get(['id', 'name', 'business_name', 'branch'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => trim(($c->business_name ?: $c->name) . ($c->branch ? ' - ' . $c->branch : ''))])->values();

        return view('attendance.sheet', compact('client', 'sheet', 'month', 'plans', 'newPin', 'moveTargets'));
    }

    /** Set or clear one guard / date / shift. Called by the grid (JSON). */
    public function mark(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'shift' => ['required', Rule::in(Attendance::SHIFTS)],
            'status' => ['required', Rule::in(array_merge(array_keys(Attendance::LABELS), ['clear']))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $date = Carbon::parse($data['date']);
        $guard = employee::findOrFail($data['employee_id']);

        if ($data['status'] === Attendance::PENDING) {
            return response()->json(['message' => 'Awaiting review is set by scans only.'], 422);
        }
        if (in_array($data['status'], [Attendance::PRESENT, Attendance::ABSENT], true) && $date->isAfter(Carbon::today())) {
            return response()->json(['message' => 'Present / absent cannot be marked for a future date.'], 422);
        }
        if (! Roster::isPosted($guard, $client)
            && ! Attendance::where('client_id', $client->id)->where('employee_id', $guard->id)->exists()) {
            return response()->json(['message' => 'This guard is not posted to this client.'], 422);
        }

        $existing = Attendance::where('employee_id', $guard->id)->where('work_date', $date->toDateString())->where('shift', $data['shift'])->first();
        if ($existing?->overtime_id) {
            return response()->json(['message' => 'This shift comes from overtime entry #' . $existing->overtime_id
                . '. Change or delete the overtime entry instead.'], 422);
        }
        if ($existing && (int) $existing->client_id !== (int) $client->id) {
            return response()->json(['message' => 'This guard is already recorded at another client for that shift.'], 422);
        }

        if ($data['status'] === 'clear') {
            $existing?->delete();
        } elseif ($existing) {
            $existing->fill([
                'status' => $data['status'],
                'reason' => ShiftReasons::FOR_STATUS[$data['status']] ?? null,
                'marked_by' => Auth::id(),
                'note' => $data['note'] ?? $existing->note,
            ])->save();
        } else {
            Attendance::create([
                'client_id' => $client->id, 'employee_id' => $guard->id,
                'work_date' => $date->toDateString(), 'shift' => $data['shift'],
                'status' => $data['status'], 'reason' => ShiftReasons::FOR_STATUS[$data['status']] ?? null, 'source' => 'manual',
                'marked_by' => Auth::id(), 'note' => $data['note'] ?? null,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Send a guard posted here to another client for one shift ("moved" / relief).
     * The shift is recorded as Present at the other client with moved_from_client_id = this
     * client, so this sheet shows "M - moved to ..." and the guard gets no absent deduction.
     * action=cancel undoes it.
     */
    public function move(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'shift' => ['required', Rule::in(Attendance::SHIFTS)],
            'to_client_id' => ['nullable', 'integer', 'required_without:action'],
            'action' => ['nullable', Rule::in(['cancel'])],
        ]);
        $guard = employee::findOrFail($data['employee_id']);
        $date = Carbon::parse($data['date']);
        $existing = Attendance::where('employee_id', $guard->id)->where('work_date', $date->toDateString())->where('shift', $data['shift'])->first();

        if (($data['action'] ?? null) === 'cancel') {
            if (! $existing || (int) $existing->moved_from_client_id !== (int) $client->id || $existing->overtime_id || $existing->source === 'scan') {
                return response()->json(['message' => 'There is no move from this client to cancel for that shift.'], 422);
            }
            $existing->delete();

            return response()->json(['ok' => true]);
        }

        if (! Roster::isPosted($guard, $client)) {
            return response()->json(['message' => 'Only guards posted to this client can be moved from here.'], 422);
        }
        if ($date->isAfter(Carbon::today())) {
            return response()->json(['message' => 'A move can only be recorded for today or earlier.'], 422);
        }
        $to = Client::find($data['to_client_id']);
        if (! $to || (int) $to->id === (int) $client->id || ! Roster::canManage(Auth::user(), $to)) {
            return response()->json(['message' => 'Choose another client you can take attendance for.'], 422);
        }
        if ($existing?->overtime_id) {
            return response()->json(['message' => 'This shift comes from overtime entry #' . $existing->overtime_id . '. Change the overtime entry instead.'], 422);
        }
        if ($existing && (int) $existing->client_id !== (int) $client->id) {
            return response()->json(['message' => 'This guard is already recorded at another client for that shift.'], 422);
        }
        if ($existing && $existing->source === 'scan' && $existing->status === Attendance::PRESENT) {
            return response()->json(['message' => 'This guard signed in here for that shift, so they weren\'t moved.'], 422);
        }

        DB::transaction(function () use ($existing, $guard, $client, $to, $date, $data) {
            $existing?->delete();   // e.g. an absence marked here before the move was known
            Attendance::create([
                'client_id' => $to->id, 'moved_from_client_id' => $client->id,
                'employee_id' => $guard->id, 'work_date' => $date->toDateString(), 'shift' => $data['shift'],
                'status' => Attendance::PRESENT, 'reason' => 'Present', 'source' => 'manual', 'marked_by' => Auth::id(),
                'note' => 'Moved from ' . ($client->business_name ?: $client->name) . ' for the shift',
            ]);
        });

        return response()->json(['ok' => true, 'to' => $to->business_name ?: $to->name]);
    }

    /** Mark every unmarked day so far this month as present (one guard, or everyone with a day/night shift). */
    public function fill(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'integer'],
        ]);
        $start = ShiftPlans::monthStart($data['month']);
        $last = $start->copy()->endOfMonth()->startOfDay();
        if ($last->isAfter(Carbon::today())) {
            $last = Carbon::today();
        }

        $guards = Roster::guards($client);
        if (! empty($data['employee_id'])) {
            $guards = $guards->where('id', (int) $data['employee_id']);
        }

        $added = 0;
        $skipped = [];
        DB::transaction(function () use ($guards, $client, $start, $last, &$added, &$skipped) {
            foreach ($guards as $guard) {
                if (! in_array($guard->attendance_shift, Attendance::SHIFTS, true)) {
                    $skipped[] = $guard->name;
                    continue;
                }
                $have = Attendance::where('employee_id', $guard->id)
                    ->whereBetween('work_date', [$start->toDateString(), $last->toDateString()])
                    ->pluck('work_date')->map(fn ($d) => $d->toDateString())->flip();
                for ($d = $start->copy(); $d->lte($last); $d->addDay()) {
                    if (! isset($have[$d->toDateString()])) {
                        Attendance::create([
                            'client_id' => $client->id, 'employee_id' => $guard->id,
                            'work_date' => $d->toDateString(), 'shift' => $guard->attendance_shift,
                            'status' => Attendance::PRESENT, 'source' => 'manual', 'marked_by' => Auth::id(),
                        ]);
                        $added++;
                    }
                }
            }
        });

        $msg = "Marked {$added} unmarked day(s) present.";
        if ($skipped) {
            $msg .= ' Skipped (set Day or Night first): ' . implode(', ', $skipped) . '.';
        }

        return back()->with('success', $msg);
    }

    public function setShift(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'shift' => ['nullable', Rule::in(array_keys(Roster::SHIFTS))],
        ]);
        $guard = employee::findOrFail($data['employee_id']);
        abort_unless(Roster::isPosted($guard, $client), 422, 'This guard is not posted to this client.');
        Roster::setShift($client, $guard, $data['shift'] ?? null);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Shift updated for ' . $guard->name . '.');
    }

    public function savePlan(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate(ShiftPlans::rules() + ['effective_month' => ['required', 'date_format:Y-m']]);
        $plan = ShiftPlans::save($client, $data, $data['effective_month'], Auth::id());

        return back()->with('success', 'Shift plan saved from ' . $plan->effective_month->format('F Y') . ': '
            . $plan->day_guards . ' day, ' . $plan->night_guards . ' night guard(s).');
    }

    public function saveSite(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'attendance_radius_m' => ['required', 'integer', 'min:30', 'max:5000'],
        ]);
        $client->forceFill($data)->save();

        return back()->with('success', 'Site location saved.');
    }

    /** New secret for the QR poster: old posters stop working at once. */
    public function regenerateToken(Client $client)
    {
        $this->authorizeClient($client);
        $client->forceFill(['attendance_token' => self::newToken()])->save();

        return back()->with('success', 'New QR code created. Print the new poster and replace the old one; the old QR no longer works.');
    }

    public function poster(Client $client)
    {
        $this->authorizeClient($client);
        if (! $client->attendance_token) {
            $client->forceFill(['attendance_token' => self::newToken()])->save();
        }
        $url = route('attend.show', $client->attendance_token);
        $plan = ShiftPlans::forMonth($client->id, now());

        return view('attendance.poster', compact('client', 'url', 'plan'));
    }

    public function resetPin(Client $client, employee $employee)
    {
        $this->authorizeClient($client);
        abort_unless(Roster::isPosted($employee, $client), 422, 'This guard is not posted to this client.');

        $pin = (string) random_int(1000, 9999);
        $employee->forceFill(['attendance_pin' => Hash::make($pin)])->saveQuietly();

        return back()->with('new_pin', ['name' => $employee->name, 'id' => $employee->id, 'pin' => $pin]);
    }

    /**
     * Issue PINs to many guards at once and show printable slips (the only time the PINs are visible).
     * mode=missing: guards without a PIN; mode=all: every guard posted here (old PINs stop working).
     */
    public function issuePins(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $mode = $request->validate(['mode' => ['required', Rule::in(['missing', 'all'])]])['mode'];

        $guards = Roster::guards($client)->filter(fn ($g) => $mode === 'all' || ! $g->attendance_pin)->values();
        $slips = [];
        DB::transaction(function () use ($guards, &$slips) {
            foreach ($guards as $g) {
                $pin = (string) random_int(1000, 9999);
                employee::whereKey($g->id)->update(['attendance_pin' => Hash::make($pin)]);
                $slips[] = ['id' => $g->id, 'name' => $g->name, 'shift' => $g->attendance_shift, 'pin' => $pin];
            }
        });

        return response()
            ->view('attendance.pin-slips', ['client' => $client, 'slips' => $slips, 'mode' => $mode, 'url' => $client->attendance_token ? route('attend.show', $client->attendance_token) : null])
            ->header('Cache-Control', 'no-store, private');
    }

    public function forgetPhone(Client $client, employee $employee)
    {
        $this->authorizeClient($client);
        abort_unless(Roster::isPosted($employee, $client), 422, 'This guard is not posted to this client.');
        $employee->forceFill(['attendance_device' => null])->saveQuietly();

        return back()->with('success', $employee->name . '\'s phone was cleared. The next phone they sign in with becomes their phone.');
    }

    public function export(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $sheet = new MonthSheet($client, $this->month($request));
        $name = Str::slug(($client->business_name ?: $client->name) . ' ' . $sheet->month->format('Y-m')) . '-attendance.xlsx';

        return Excel::download(new AttendanceSheetExport($sheet), $name);
    }

    // ---------------------------------------------------------------- supervisor kiosk

    /** Phone page for a supervisor at the site: mark guards by tapping them or scanning their ID card. */
    public function kiosk(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $plan = ShiftPlans::forMonth($client->id, now());
        $slot = ShiftClock::resolve(now(), $plan);
        if (in_array($request->query('shift'), Attendance::SHIFTS, true) && $request->query('shift') !== $slot['shift']) {
            // Supervisor switched shift: same rules as a guard of that shift signing in now.
            $slot = ShiftClock::resolve(now(), $plan, $request->query('shift'));
            $slot['shift'] = $request->query('shift');
        }
        $date = $slot['date']->toDateString();

        $guards = Roster::guards($client);
        $records = Attendance::where('client_id', $client->id)->where('work_date', $date)->where('shift', $slot['shift'])
            ->get()->keyBy('employee_id');
        [$onShift, $others] = $guards->partition(fn ($g) => ! in_array($g->attendance_shift, ['day', 'night'], true) || $g->attendance_shift === $slot['shift']);

        return view('attendance.kiosk', [
            'client' => $client, 'plan' => $plan, 'date' => $slot['date'], 'shift' => $slot['shift'],
            'onShift' => $onShift->values(), 'others' => $others->values(), 'records' => $records,
            'required' => (int) ($plan?->{$slot['shift'] . '_guards'} ?? 0),
        ]);
    }

    public function kioskMark(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'employee' => ['required', 'string', 'max:30'],   // id, "FWSS 45" or the ID-card QR text "ISSOBS-GUARD:45"
            'date' => ['required', 'date_format:Y-m-d'],
            'shift' => ['required', Rule::in(Attendance::SHIFTS)],
            'status' => ['required', Rule::in([Attendance::PRESENT, Attendance::ABSENT])],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);
        $date = Carbon::parse($data['date']);
        if ($date->lt(Carbon::yesterday()) || $date->gt(Carbon::today())) {
            return response()->json(['ok' => false, 'message' => 'The kiosk only marks the current shift. Use the monthly sheet for other days.'], 422);
        }

        $id = ScanRecorder::employeeId(preg_replace('/^ISSOBS-GUARD:/i', '', trim($data['employee'])));
        $guard = $id ? employee::find($id) : null;
        if (! $guard) {
            return response()->json(['ok' => false, 'message' => 'Guard not found. Check the ID card or number.'], 422);
        }

        $result = KioskRecorder::record($client, $guard, $date->toDateString(), $data['shift'], $data['status'], Auth::user(), $data);

        return response()->json($result + ['employee_id' => $guard->id], $result['ok'] ? 200 : 422);
    }

    /** Printable ID cards with a QR for the kiosk to scan (one per guard posted here). */
    public function idCards(Client $client)
    {
        $this->authorizeClient($client);

        return view('attendance.id-cards', ['client' => $client, 'guards' => Roster::guards($client)]);
    }

    // ---------------------------------------------------------------- review queue

    public function review(Request $request)
    {
        $clientIds = Roster::clientsFor(Auth::user())->pluck('id')->all();
        $items = Attendance::with(['client:id,name,business_name,branch', 'employee:id,name,phone_number,client_id'])
            ->where('status', Attendance::PENDING)
            ->whereNull('overtime_id')   // overtime entries are approved on the overtime page
            ->whereIn('client_id', $clientIds ?: [0])
            ->orderBy('work_date')->orderBy('checked_in_at')
            ->paginate(50);

        return view('attendance.review', compact('items'));
    }

    public function decide(Request $request, Attendance $attendance)
    {
        $this->authorizeClient($attendance->client);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'trust_phone' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        abort_unless($attendance->status === Attendance::PENDING, 422, 'This sign-in was already reviewed.');
        abort_if((bool) $attendance->overtime_id, 422, 'This comes from an overtime entry: approve or reject the overtime instead.');

        $guard = $attendance->employee;
        $fromElsewhere = $guard && $guard->client_id && (int) $guard->client_id !== (int) $attendance->client_id;

        // A guard from another post: rejecting means they weren't here, which is not an absence at
        // this client (their own post's sheet decides that), so the sign-in is just removed.
        if ($data['decision'] === 'reject' && $fromElsewhere) {
            $attendance->delete();

            return back()->with('success', 'Rejected: the sign-in by ' . $guard->name . ' was removed. They are not posted here, so no absence was recorded.');
        }

        $note = trim(implode('; ', array_filter([$attendance->note, $data['note'] ?? null])));
        $attendance->fill([
            // Approving a guard from another post records them as moved here for the shift.
            'moved_from_client_id' => $data['decision'] === 'approve' && $fromElsewhere ? $guard->client_id : $attendance->moved_from_client_id,
            'status' => $data['decision'] === 'approve' ? Attendance::PRESENT : Attendance::ABSENT,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'note' => $note ?: null,
        ])->save();

        if ($data['decision'] === 'approve' && ! empty($data['trust_phone']) && $attendance->device_id) {
            employee::where('attendance_device', $attendance->device_id)->where('id', '!=', $attendance->employee_id)
                ->update(['attendance_device' => null]);
            employee::whereKey($attendance->employee_id)->update(['attendance_device' => $attendance->device_id]);
        }

        return back()->with('success', ($data['decision'] === 'approve' ? 'Approved: ' : 'Rejected (marked absent): ')
            . ($attendance->employee->name ?? 'guard') . ', ' . $attendance->work_date->format('D j M') . ' ' . $attendance->shift . ' shift.');
    }

    // ---------------------------------------------------------------- helpers

    private function authorizeClient(?Client $client): void
    {
        abort_unless($client && Roster::canManage(Auth::user(), $client), 403, 'You cannot take attendance for this client.');
    }

    private function month(Request $request): Carbon
    {
        $m = (string) $request->query('month', '');

        return ShiftPlans::monthStart(preg_match('/^\d{4}-\d{2}$/', $m) ? $m : now());
    }

    public static function newToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Client::where('attendance_token', $token)->exists());

        return $token;
    }
}
