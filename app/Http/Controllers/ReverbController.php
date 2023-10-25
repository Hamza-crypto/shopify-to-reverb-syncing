<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

class ReverbController extends Controller
{
    public function reverb_call($api_endpoint, $method = 'GET', $body = [])
    {
        $token = env('REVERB_API_KEY');
        $url = sprintf('https://api.reverb.com/api/%s', $api_endpoint);

        if ($method == 'PUT') {
            $response = Http::withToken($token)->put($url, $body);

        } elseif ($method == 'GET') {
            $response = Http::withToken($token)->get($url);
        }

        return $response->json();
    }

    public function update_inventory_on_reverb($sku, $inventory_count)
    {
        $api_endpoint = "my/listings?sku=$sku&state=all";
        $response = $this->reverb_call($api_endpoint);

        try {
            // $msg = convertResponseToString($response);
            // DiscordAlert::message($msg);
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
        // $msg = convertResponseToString($response);
        // DiscordAlert::message($msg);
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
        $order = $this->reverb_call($url);
        return $order;
    }


    public function get_reverb_product($product_id) {
        return $this->reverb_call("listings/$product_id");
    }

    public function fetch_orders_from_db()
    {
        return Order::where('inventory_updated', 0)->get();
    }
}
