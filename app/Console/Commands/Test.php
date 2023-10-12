<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

class Test extends Command
{
    protected $signature = 'reverb-test';
    protected $description = 'It tests if cron job is working';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $result = 'This is just test page ' . time();
        echo $result;
        DiscordAlert::message($result);
    }
}
