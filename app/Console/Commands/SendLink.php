<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated legacy survey blast command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->warn('email:link is deprecated. Use survey waves to assign and send invitations.');

        return self::SUCCESS;
    }
}
