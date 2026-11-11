<?php

namespace App\Support\Attendance;

use RuntimeException;

/** @internal OfflineSync: rolls back a sync item that should be tried again later (nothing is recorded). */
final class RetryLater extends RuntimeException
{
    /** @param  array<string, mixed>  $result */
    public function __construct(public array $result)
    {
        parent::__construct((string) ($result['message'] ?? 'retry'));
    }
}
