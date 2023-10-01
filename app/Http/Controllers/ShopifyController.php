<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class ShopifyController extends Controller
{
    public function shopify_call($api_endpoint, $query = [], $method = 'GET', $request_headers = [])
    {
        $shop = env('SHOP_NAME');
        $token = env('SHOPIFY_TOKEN');
        $version = env('SHOPIFY_VERSION');
        $url = sprintf('https://%s.myshopify.com/admin/api/%s/%s', $shop, $version, $api_endpoint);

        if (! is_null($query) && in_array($method, ['GET', 'DELETE'])) {
            $url = $url.'?'.http_build_query($query);
        }

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        if (! is_null($token)) {
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

    public function get_shopify_product_inventory_count($product_id)
    {
        $url = sprintf('products/%s.json', $product_id);
        $response = $this->shopify_call($url);
        if ($response['product']['product_type'] == 'drum kit') { //If this product is within specific product type
            return [
                'sku' => $response['product']['variants'][0]['sku'],
                'inventory_quantity' => $response['product']['variants'][0]['inventory_quantity'],
            ];

        } else {
            return null;
        }

    }
}
