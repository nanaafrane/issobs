<?php

namespace App\Http\Controllers;

use App\Models\Client;
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

        return view('attendance.scan', compact('client', 'token'));
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

    private function client(string $token): Client
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $token), 404);

        return Client::where('attendance_token', $token)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->firstOr(fn () => abort(404, 'This QR code is no longer in use. Please tell your supervisor.'));
    }
}
