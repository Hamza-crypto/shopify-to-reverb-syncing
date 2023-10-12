<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

class ShopifyController extends Controller
{
    public function shopify_call($api_endpoint, $query = [], $method = 'GET', $request_headers = [])
    {
        $shop = env('SHOP_NAME');
        $token = env('SHOPIFY_TOKEN');
        $version = env('SHOPIFY_VERSION');
        $url = sprintf('https://%s.myshopify.com/admin/api/%s/%s', $shop, $version, $api_endpoint);

        if (! is_null($query) && in_array($method, ['GET', 'DELETE'])) {
            $url = $url . '?' . http_build_query($query);
        }

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        if (! is_null($token)) {
            $headers['X-Shopify-Access-Token'] = $token;
        }

        $response = Http::withHeaders($headers)
            ->withOptions([
                'verify' => false, // Disable SSL verification, use with caution!
            ])
            ->{$method}($url, $query);

        return $response->json();
    }

    public function shopify_call2($url, $query = [], $method = 'GET')
    {
        $token = env('SHOPIFY_TOKEN');

        if (!empty($query)) {
            $url .= http_build_query($query);
        }

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        if (! is_null($token)) {
            $headers['X-Shopify-Access-Token'] = $token;
        }
        dump($url);
        $response = Http::withHeaders($headers)
            ->withOptions([
                'verify' => false, // Disable SSL verification, use with caution!
            ])
            ->{$method}($url);
        return $response;
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

    public function get_shopify_product_inventory_item($product_id)
    {
        $url = sprintf('products/%s.json', $product_id);
        $response = $this->shopify_call($url);
        if ($response['product']['product_type'] == 'drum kit') { //If this product is within specific product type
            return [
                'inventory_quantity' => $response['product']['variants'][0]['inventory_quantity'],
                'inventory_item_id' => $response['product']['variants'][0]['inventory_item_id'],
            ];

        } else {
            return null;
        }

    }

    public function fetchAndStoreProducts()
    {
        $shop = env('SHOP_NAME');
        $version = env('SHOPIFY_VERSION');
        $api_endpoint = 'products.json?';
        $nextPageUrl = sprintf('https://%s.myshopify.com/admin/api/%s/%s', $shop, $version, $api_endpoint);

        $productsFetched = 0;

        $queryParams = [
                // 'product_type' => 'drum kit',
                'limit' => 250
            ];

        do {
            $response = $this->shopify_call2($nextPageUrl, $queryParams);
            $products = $response['products'];


            foreach ($products as $product) {
                $this->store_product($product);
                $productsFetched++;
            }

            // Extract the next page URL from the "Link" header
            $nextPageUrl = $this->getNextPageUrl($response);
            $queryParams = []; // Because we are getting full url from headers along with query params

        } while ($nextPageUrl);

        return response()->json(['message' => "Fetched and stored $productsFetched products."]);
    }

    private function getNextPageUrl($response)
    {
        $linkHeader = $response->header('Link');

        if (preg_match('/<([^>]*)>; rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function fetch_products_from_files($num_files = 4)
    {
        $all_products = [];

        for ($i = 1; $i <= $num_files; $i++) {
            $file_path = public_path('response' . $i . '.json');

            if (file_exists($file_path)) {
                $json_data = file_get_contents($file_path);
                $products = json_decode($json_data, true);
                $all_products = array_merge($all_products, $products['products']);
            }
        }

        return $all_products;
    }

    public function store_product($product)
    {
        try {
            if($product['variants'][0]['sku'] != null) {
                Product::create([
                                        'product_id' => $product['id'],
                                        'name' => $product['title'],
                                        'category' => $product['product_type'],
                                        'sku' => $product['variants'][0]['sku'],
                                        'quantity' => $product['variants'][0]['inventory_quantity'],
                                    ]);
            }

        } catch(\Exception $e) {
            echo $e->getMessage() . '<br>';
        }



    }

    public function update_inventory($inventory_item_id, $quantity, $db_order_id, $shopify_product_id)
    {
        $param = [
            'inventory_item_id' => $inventory_item_id,
            'location_id' => env('SHOPIFY_LOCATION_ID'),
            'available_adjustment' => $quantity
        ];

        $this->shopify_call('inventory_levels/adjust.json', $param, 'POST');

        Order::where('id', $db_order_id)->update(['inventory_updated' => 1]);

        DiscordAlert::message(sprintf('Inventory updated on shopify for product %s', $shopify_product_id));
    }
}