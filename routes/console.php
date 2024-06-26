<?php

use App\Console\Commands\EnsureConnectionToBackupDestinationsCommand;
use App\Console\Commands\ExecuteScheduledBackupTasksCommand;
use App\Console\Commands\ResetInoperativeBackupTasksCommand;
use App\Console\Commands\VerifyConnectionToRemoteServersCommand;

Schedule::command(ExecuteScheduledBackupTasksCommand::class)
    ->everyMinute();

Schedule::command(VerifyConnectionToRemoteServersCommand::class)
    ->everySixHours();

Schedule::command(EnsureConnectionToBackupDestinationsCommand::class)
    ->twiceDaily(2, 14)->everySixHours();

Schedule::command(ResetInoperativeBackupTasksCommand::class)
    ->everyMinute();
