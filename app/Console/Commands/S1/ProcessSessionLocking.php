<?php

namespace App\Console\Commands\S1;

use App\Jobs\S1\LockSessionSignups;
use Illuminate\Console\Command;

class ProcessSessionLocking extends Command
{
    protected $signature = 's1:process-session-locking';
    protected $description = 'Process S1 session signup locking';

    public function handle()
    {
        $this->info('Dispatching session locking job...');
        LockSessionSignups::dispatch();
        $this->info('Job dispatched successfully');
        
        return Command::SUCCESS;
    }
}