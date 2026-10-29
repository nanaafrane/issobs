<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests travel to many dates; the subscription lock is tested on its own (SubscriptionLockTest).
        config(['subscription.enforce' => false]);
    }
}
