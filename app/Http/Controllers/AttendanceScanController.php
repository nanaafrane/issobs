<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\Attendance\OfflineSync;
use App\Support\Attendance\PinSeal;
use App\Support\Attendance\ScanRecorder;
use Illuminate\Http\Request;

/**
 * The page the QR poster at a client's site opens. No login: the guard proves who
 * they are with FWSS number + PIN, and ScanRecorder checks where and when.
 * The poster's token is a 32-character secret; operations can replace it if a
 * photo of the poster gets passed around.
 */
class AttendanceScanController extends Controller
{
    public function show(string $token)
    {
        $client = $this->client($token);

        return view('attendance.scan', [
            'client' => $client, 'token' => $token,
            'offline' => config('attendance.offline.enabled'),
            'pinKey' => config('attendance.offline.enabled') ? PinSeal::publicKeySpki() : null,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $client = $this->client($token);
        $input = $request->validate([
            'employee_number' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $result = ScanRecorder::record($client, $input, now(), $request->ip());
        unset($result['attendance']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Upload from the phone's outbox (one or more sign-ins, possibly made offline).
     * Each item is recorded once by its uuid; see OfflineSync. A poster replaced while the
     * phone was offline answers 410 so the phone can tell the guard to sign in again.
     */
    public function sync(Request $request, string $token, OfflineSync $sync)
    {
        abort_unless(config('attendance.offline.enabled'), 404);
        $client = $this->activeClient($token);
        if (! $client) {
            return response()->json(['gone' => true, 'message' => 'This QR poster was replaced or is no longer in use. Scan the new poster and sign in again.'], 410);
        }

        $data = $request->validate([
            'sent_at' => ['nullable', 'numeric'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['array'],
            'items.*.uuid' => ['required', 'string', 'max:64'],
            'items.*.captured_at' => ['nullable', 'numeric'],
            'items.*.offline' => ['nullable', 'boolean'],
            'items.*.employee_number' => ['required', 'string', 'max:20'],
            'items.*.pin' => ['nullable', 'string', 'max:10'],
            'items.*.pin_sealed' => ['nullable', 'string', 'max:1024'],
            'items.*.latitude' => ['nullable', 'numeric'],
            'items.*.longitude' => ['nullable', 'numeric'],
            'items.*.accuracy' => ['nullable', 'numeric'],
            'items.*.device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $skew = OfflineSync::skew($data['sent_at'] ?? null);
        $results = array_map(fn ($item) => $sync->scan($client, $item, $skew, $request->ip()), $data['items']);

        return response()->json(['results' => $results, 'server_time' => now()->getTimestampMs()]);
    }

    private function activeClient(string $token): ?Client
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            return null;
        }

        return Client::where('attendance_token', $token)->whereRaw('LOWER(status) = ?', ['active'])->first();
    }

    private function client(string $token): Client
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $token), 404);

        return Client::where('attendance_token', $token)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->firstOr(fn () => abort(404, 'This QR code is no longer in use. Please tell your supervisor.'));
    }
}
