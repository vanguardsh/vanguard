<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class GreetingService
{
    public function auto(string $timezone = 'UTC'): string
    {
        $currentTime = Carbon::now($timezone);
        $hour = $currentTime->hour;

        if ($hour < 12) {
            return __('Good morning');
        }

        if ($hour < 18) {
            return __('Good afternoon');
        }

        return __('Good evening');
    }
}
