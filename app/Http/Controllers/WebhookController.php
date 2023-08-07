<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

class WebhookController extends Controller
{
    public function shopify_new_order(Request $request)
    {
        /*
         * This function get webhook notification from Shopify when new order is created
         */
        $product_id = $request->line_items[0]['product_id'];

        app('log')->channel('shopify')->info($request->all());

        $msg = "New order created for product " . $product_id;

        DiscordAlert::message($msg);
        $inventory_count = $this->get_shopify_product_inventory_count($product_id);
        if ($inventory_count == null) {
            return;
        }

        $msg = "Inventory count: " . $inventory_count;
        DiscordAlert::message($msg);

    }

    public function get_shopify_product_inventory_test($product_id = '6658541518928')
    {
        //Route for test
        $inventory_count = $this->get_shopify_product_inventory_count($product_id);
        return $inventory_count;
    }

    public function get_shopify_product_inventory_count($product_id)
    {
        $url = sprintf("products/%s.json", $product_id);
        $response = $this->shopify_call($url);
        if ($response['product']['product_type'] == 'drum kit') { //If this product is within specific product type
            return $response['product']['variants'][0]['inventory_quantity'];

        } else {
            return null;
        }

    }

    public function shopify_call($api_endpoint, $query = array(), $method = 'GET', $request_headers = array())
    {
        $shop = env('SHOP_NAME');
        $token = env('SHOPIFY_TOKEN');
        $version = env('SHOPIFY_VERSION');
        $url = sprintf('https://%s.myshopify.com/admin/api/%s/%s', $shop, $version, $api_endpoint);

        if (!is_null($query) && in_array($method, array('GET', 'DELETE'))) {
            $url = $url . "?" . http_build_query($query);
        }

        $headers = array();
        $headers['Content-Type'] = 'application/json';
        if (!is_null($token)) {
            $headers['X-Shopify-Access-Token'] = $token;
        }

        $response = Http::withHeaders($headers)
            ->withOptions([
                'verify' => false, // Disable SSL verification, use with caution!
                'timeout' => 30, // Set the connection timeout
            ])
            ->{$method}($url, $query);

        return $response->json();
    }

}
