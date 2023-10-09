<?php

namespace App\Console\Commands;

use App\Http\Controllers\ShopifyController;
use Illuminate\Console\Command;

class FetchProducts extends Command
{
    protected $signature = 'fetch:shopify-products';

    protected $description = 'Fetch products from Shopify and store into database';

    public function handle()
    {
        $shopify_controller = new ShopifyController();
        $products = $shopify_controller->fetch_products();
        foreach ($products as $product) {
            // dd($product);
            $shopify_controller->store_product($product);
        }

        $this->info('Products Stored');
    }
}
