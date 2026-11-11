<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\Attendance\OfflineSync;
use Illuminate\Http\Request;

/**
 * Upload from the supervisor kiosk's outbox (marks possibly made offline). Authenticated with the
 * kiosk's own token (Bearer, ability "attendance-kiosk"), not the login session, so marks saved
 * overnight still upload after the session has expired. See docs/offline-sync.md.
 */
class AttendanceSyncController extends Controller
{
    public function kiosk(Request $request, OfflineSync $sync)
    {
        abort_unless(config('attendance.offline.enabled'), 404);
        abort_unless($request->user()?->tokenCan('attendance-kiosk'), 403, 'This token cannot upload attendance.');

        $data = $request->validate([
            'sent_at' => ['nullable', 'numeric'],
            'client_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array'],
            'items.*.uuid' => ['required', 'string', 'max:64'],
            'items.*.captured_at' => ['nullable', 'numeric'],
            'items.*.offline' => ['nullable', 'boolean'],
            'items.*.employee' => ['required', 'string', 'max:30'],
            'items.*.date' => ['required', 'string', 'max:10'],
            'items.*.shift' => ['required', 'string', 'max:10'],
            'items.*.status' => ['required', 'string', 'max:10'],
            'items.*.latitude' => ['nullable', 'numeric'],
            'items.*.longitude' => ['nullable', 'numeric'],
            'items.*.accuracy' => ['nullable', 'numeric'],
            'items.*.device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $client = Client::find($data['client_id']);
        if (! $client) {
            return response()->json(['message' => 'Client not found.'], 404);
        }

        $skew = OfflineSync::skew($data['sent_at'] ?? null);
        $results = array_map(fn ($item) => $sync->kiosk($client, $request->user(), $item, $skew), $data['items']);

        return response()->json(['results' => $results, 'server_time' => now()->getTimestampMs()]);
    }
}
