<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReverbController;
use App\Http\Controllers\ShopifyController;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpParser\Node\Stmt\Continue_;

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
        // $oneHourAgo = $currentDateTime->subDays(10);

        $orders = $reverb_controller->fetch_all_orders($oneHourAgo);

        foreach ($orders as $order) {
            if ($order['status'] == 'shipped') {

                $productId = $order['product_id'];
                $reverb_product = $reverb_controller->get_reverb_product($productId);
                $reverb_inventory = $reverb_product['inventory'];
                $sku = $reverb_product['sku'];

                // Get product details
                $productObject = Product::where('sku', $sku)->where('category', 'drum kit')->first();

                if(!$productObject) {
                    continue;
                }

                //Get shopify product current inventory count and inventory_item_id
                $shopifyProduct = $shopify_controller->get_shopify_product_inventory_count($productObject->product_id);

                $adjustmentQuantity = $reverb_inventory - $shopifyProduct['inventory_quantity'];
                if($adjustmentQuantity != 0) {
                    $shopify_controller->update_inventory($shopifyProduct['inventory_item_id'], $adjustmentQuantity);
                }
            }
        }

        $this->info('Orders processed successfully.');
    }
}