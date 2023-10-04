<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReverbController;
use App\Http\Controllers\ShopifyController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessOrders extends Command
{
    protected $signature = 'process:reverb-orders';
    protected $description = 'Process orders and update product quantities';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $reverb_controller = new ReverbController();
        $shopify_controller = new ShopifyController();

        $currentDateTime = Carbon::now();

        // Subtract 1 hour
        $oneHourAgo = $currentDateTime->subHour();

        // Format the date and time in the desired format
        // $start_date = $oneHourAgo->format("Y-m-d\TH:i:sP");
        $start_date = "2023-10-03T00:00:00+00:00";
        $orders = $reverb_controller->fetch_all_orders($start_date);

        foreach ($orders as $order) {
            if ($order['status'] == 'shipped') {

                $productId = $order['product_id'];
                $reverb_product = $reverb_controller->get_reverb_product($productId);
                $inventory = $reverb_product['inventory'];
                $sku = $reverb_product['sku'];
                dump($inventory, $sku);
                // Get product details
                // $product = $productService->getProductDetails($productId);

                // Update product quantity on Shopify
                // $productService->updateProductQuantityOnShopify($product->id, $newQuantity);
            }
        }

        $this->info('Orders processed successfully.');
    }
}
