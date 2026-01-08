<?php

namespace App\Console\Commands\S1;

use App\Jobs\S1\SelectSessionParticipants;
use Illuminate\Console\Command;

class ProcessParticipantSelection extends Command
{
    protected $signature = 's1:process-participant-selection';
    protected $description = 'Process S1 session participant selection';

    public function handle()
    {
        $this->info('Dispatching participant selection job...');
        SelectSessionParticipants::dispatch();
        $this->info('Job dispatched successfully');
        
        return Command::SUCCESS;
    }
}