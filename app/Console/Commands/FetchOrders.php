<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReverbController;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchOrders extends Command
{
    protected $signature = 'fetch:reverb-orders';
    protected $description = 'It fetches reverb orders and store in database';

    public function handle()
    {
        $reverb_controller = new ReverbController();

        $currentDateTime = Carbon::now();

        $tenDaysAgo = $currentDateTime->subDays(10);

        $orders = $reverb_controller->fetch_all_orders($tenDaysAgo);

        foreach ($orders as $order) {
            Order::updateOrCreate(
                [
                'order_id' => $order['order_number']
            ],
                ['status' => $order['status']]
            );
        }

        $this->info('Orders Saved successfully.');
    }
}