<?php

namespace App\Console\Commands;

use App\Http\Controllers\WebshippyOrdersController;
use Illuminate\Console\Command;

class CheckWebshippyOrderStatus extends Command
{
    protected $signature = 'check:webshippy-order-status';

    protected $description = 'It checks the webshippy order status and update the order status back in Leadvertex if status is set to refused';

    public function handle()
    {
        $controller = new WebshippyOrdersController();
        $controller->UpdateOrders();

        return Command::SUCCESS;
    }
}
