<?php

use App\Http\Controllers\ChartController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Spatie\DiscordAlerts\Facades\DiscordAlert;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */
Route::view('/', 'welcome');

Route::get('/test2', function () {
    // Path to the JSON file in the public directory
    $filePath = public_path('reverb-orders.json');

    // Read the JSON file
    $jsonContent = File::get($filePath);
    $data = json_decode($jsonContent, true);

    $orderDetails = [];
    $ordersWithoutInventory = [];

    // Collect product_id, remaining listing inventory, and UUID for each order
    foreach ($data['orders'] as $order) {
        $productId = $order['product_id'];

        // Check if 'remaining_listing_inventory' key exists in the order
        if (isset($order['remaining_listing_inventory'])) {
            $remainingInventory = $order['remaining_listing_inventory'];
        } else {
            // If the key doesn't exist, add the UUID to the separate array
            $ordersWithoutInventory[] = $order['uuid'];
            $remainingInventory = null; // Set remaining inventory to null for these orders
        }

        // Add order details to the array
        $orderDetails[] = [
            'product_id' => $productId,
            'remaining_listing_inventory' => $remainingInventory,
        ];
    }

    return response()->json([
        'order_details' => $orderDetails,
        'orders_without_inventory' => $ordersWithoutInventory,
    ]);
});

Route::get('/test', function () {
    $result = 'This is just test page'.time();
    echo $result;
    DiscordAlert::message($result);
});

Route::get('/reset', function () {
    Artisan::call('migrate:fresh');
});

Route::prefix('shopify')->controller(WebhookController::class)->group(function () {
    Route::post('new_order', 'shopify_new_order'); // webhook when new order is placed on shopify
    Route::post('product_updated', 'shopify_product_updated'); //webhook when product inventory quantity is updated  in shopify admin dashboard
});

Route::controller(ChartController::class)->group(function () {
    Route::get('income-chart', 'index');
    Route::post('income-chart', 'store')->name('chart.store');
    Route::get('chart-data', 'chart_data')->name('chart.data');
});
