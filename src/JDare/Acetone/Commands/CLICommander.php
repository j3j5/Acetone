<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Acetone;

class Query extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acetone:run {type} {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the Twitter api';

    private $dumper;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->dumper = new \Illuminate\Support\Debug\Dumper;
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

        Acetone::$type($url);
    }

}
