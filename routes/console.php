<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// We now use the command 'posts:process-scheduled' instead of scheduling the job directly
// See app/Console/Kernel.php for the scheduled command

