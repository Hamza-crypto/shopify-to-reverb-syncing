<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Spatie\DiscordAlerts\Facades\DiscordAlert;
use Illuminate\Support\Facades\Log;

class ReverbController extends Controller
{
    public function reverb_call($api_endpoint, $method = 'GET', $body = [])
    {
        $token = env('REVERB_API_KEY');
        $url = sprintf('%s/%s', env('REVERB_BASE_URL'), $api_endpoint);

        if ($method == 'PUT') {
            $response = Http::withToken($token)
             ->withHeaders([
                'Content-Type' => 'application/hal+json',
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0'
            ])->put($url, $body);

        } elseif ($method == 'GET') {
            $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/hal+json',
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0'
            ])->get($url);
        } elseif ($method == 'POST') {
            $response = Http::withToken($token)
             ->withHeaders([
                'Content-Type' => 'application/hal+json',
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0'
            ])->post($url, $body);
        }

        return $response->json();
    }

    public function update_inventory_on_reverb($sku, $inventory_count)
    {
        $api_endpoint = "my/listings?sku=$sku&state=all";
        $response = $this->reverb_call($api_endpoint);

        try {
            $listing_id = $response['listings'][0]['id'];
        } catch (\Exception $e) {
            return null;
        }

        // $listing_id = '70161325'; //This id is for testing purpose
        $api_endpoint = "listings/$listing_id";
        $body = [
            'has_inventory' => true,
            'inventory' => $inventory_count,
        ];

        $response = $this->reverb_call($api_endpoint, 'PUT', $body);
        $msg = sprintf('Inventory updated on Reverb for sku %s with inventory count %s', $sku, $inventory_count);
        DiscordAlert::message($msg);
    }

    public function fetch_all_orders($start_date = "2023-09-20T12:00-00:00")
    {
        $url = sprintf("%s%s", "my/orders/selling/all?updated_start_date=", $start_date);
        $orders = $this->reverb_call($url);
        return $orders['orders'];
    }

    public function fetch_single_order($order_id)
    {
        $url = sprintf("%s%s", "my/orders/selling/", $order_id);
        return $this->reverb_call($url);
    }

    public function get_reverb_product($product_id)
    {
        return $this->reverb_call("listings/$product_id");
    }

    public function fetch_orders_from_db()
    {
        return Order::where('inventory_updated', 0)->get();
    }

    public function create_listing()
    {

        $products = Product::where('category', env('SHOPIFY_PREFFERED_CATEGORY'))->where('synced', 0)->get();
        if (!$products) {
            // No unsynced products found
            return;
        }

        $api_endpoint = "listings";
        foreach($products as $product) {
            $full_data = $product->full_data;

            $body = [
                'categories' => [
                        [
                            'uuid' => "d3a11618-98ef-4488-9dc0-84410675aa44",
                        ]
                    ],

                "photos" => collect($full_data['images'])->pluck('src')->toArray(),
                "description" => strip_tags($full_data['body_html']),

                "price" => [
                    'amount' => $full_data['variants'][0]['price'],
                    'currency' => 'USD',
                ],
                "title" => $full_data['title'],

                "sku" => $full_data['variants'][0]['sku'],

                "has_inventory" => true,
                "inventory" => $full_data['variants'][0]['inventory_quantity'],
                "offers_enabled" => true,

            ];

            $token = env('REVERB_API_KEY');
            $url = sprintf('%s/%s', env('REVERB_BASE_URL'), $api_endpoint);
            $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/hal+json',
                'Accept' => 'application/hal+json',
                'Accept-Version' => '3.0'
            ])
            ->post($url, $body);
            dump($response->status(), $response->json());
            Log::info($response->json());

            if(in_array($response->status(), [201, 202])) {

                Product::where('id', $product->id)->update([
                    'synced' => 1
                ]);

            }
            // break;

        }
    }


}