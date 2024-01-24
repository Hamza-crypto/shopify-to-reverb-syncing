<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

class WebhookController extends Controller
{
    protected $reverb;

    protected $shopify;

    public function __construct(ReverbController $reverb, ShopifyController $shopify)
    {
        $this->reverb = $reverb;
        $this->shopify = $shopify;
    }

    public function shopify_new_order(Request $request)
    {
        /*
         * This function get webhook notification from Shopify when new order is created
         */
        $product_id = $request->line_items[0]['product_id'];

        app('log')->channel('shopify')->info($request->all());

        $msg = 'New order created for product '.$product_id;

        DiscordAlert::message($msg);
        $response = $this->shopify->get_shopify_product_inventory_count($product_id);

        if ($response == null) {
            return;
        }

        $msg = 'Inventory count: '.$response['inventory_quantity'];
        DiscordAlert::message($msg);

        $this->reverb->update_inventory_on_reverb($response['sku'], $response['inventory_quantity']);
    }

    public function shopify_product_updated(Request $request)
    {
        /*
         * This function get webhook notification from Shopify when product is updated from admin dashboard
         */
        if ($request->product_type != env('SHOPIFY_PREFFERED_CATEGORY')) {
            return 200;
        }

        $sku = $request->variants[0]['sku'];
        $inventory_quantity = $request->variants[0]['inventory_quantity'];

        $this->reverb->update_inventory_on_reverb($sku, $inventory_quantity);
    }

    public function shopify_new_product_added(Request $request)
    {
        /*
         * This function get webhook notification from Shopify when new product is added
         */
        if ($request->product_type != env('SHOPIFY_PREFFERED_CATEGORY')) {
            return 200;
        }

        $this->reverb->create_listing2($request);
    }
}