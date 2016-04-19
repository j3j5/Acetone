<?php

namespace JDare\Acetone\Commands;

use Illuminate\Console\Command;
use Acetone;
use Log;

class CLICommander extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acetone:run
                            {type : Type of command to run (purge, ban, banmany or refresh)}
                            {url : The URL or pattern to be used with the command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a command against Varnish';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('type');
        $url = $this->argument('url');

        $response = Acetone::$type($url);
        Log::debug($response->getReasonPhrase());
    }

}
