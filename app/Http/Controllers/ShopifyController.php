<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

    public function shopify_call2($api_endpoint, $query = [], $method = 'GET', $request_headers = [])
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

    public function fetch_products($start_id = null)
    {
        // $file_path = public_path('response5.json');
        // $json_data = file_get_contents($file_path);
        // $products = json_decode($json_data, true);
        // return $products['products'];

        $url = "products.json?collection_id=266922590288&limit=3";
        if ($start_id) {
            $url .= "&since_id={$start_id}";
        }
        //Below logic does not seems to be working correctly
        $products = [];

        do {
            $response = $this->shopify_call2($url);
            dd($response['products']);
            dd($response->header('Link'));
            // Process the response and extract products
            $products = array_merge($products, $response['products']);

            // Check if there's a next page
            $next_page_url = null;
            if (isset($response['headers']['link'])) {
                $link_header = $response['headers']['link'];
                $matches = [];
                if (preg_match('/<([^>]+)>; rel="next"/', $link_header, $matches)) {
                    $next_page_url = $matches[1];
                    $url = $next_page_url;
                }
            }
        } while ($next_page_url);

        return $products;
    }

    public function store_product($product)
    {
        try {
            if($product['variants'][0]['sku'] != null) {
                Product::create([
                                        'product_id' => $product['id'],
                                        'name' => $product['title'],
                                        'sku' => $product['variants'][0]['sku'],
                                        'quantity' => $product['variants'][0]['inventory_quantity'],
                                    ]);
            }

        } catch(\Exception $e) {
            echo $e->getMessage() . '<br>';
        }



    }
}