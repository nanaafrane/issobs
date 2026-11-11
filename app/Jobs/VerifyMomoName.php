<?php

namespace App\Jobs;

use App\Models\employee;
use App\Services\Hubtel\MomoNameCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/**
 * Check one employee's MoMo number with Hubtel in the background and save the answer on the employee.
 * Runs on the queue so the "Verify" button returns at once, and a dropped connection to Hubtel
 * is retried (3 tries, a few minutes apart) instead of losing the result.
 */
class VerifyMomoName implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $employeeId)
    {
    }

    /** @return array<int, int> seconds to wait before each retry */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(MomoNameCheck $hubtel): void
    {
        $employee = employee::find($this->employeeId);
        if (! $employee) {
            return;
        }

        $result = $hubtel->check($employee->phone_number, $employee->channel);
        if (! $result['reachable'] && $this->attempts() < $this->tries) {
            throw new RuntimeException((string) $result['message']);   // retry later
        }

        $employee->message = $result['message'];
        $employee->responseCode = $result['responseCode'];
        $employee->reg_name = $result['data']['name'] ?? null;
        $employee->status_momo = $result['data']['isRegistered'] ?? null;
        $employee->save();
    }
}
