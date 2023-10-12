<?php

use App\Http\Controllers\ChartController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
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

 Route::get('/update-shopify', function () {
    $shopify = new ShopifyController();
    $shopifyProduct = $shopify->get_shopify_product_inventory_item('7901765107936');
    $reverb_inventory = 3;

    dump("Previous : " . $shopifyProduct['inventory_quantity']);
    dump("New : " . $reverb_inventory);
    $adjustmentQuantity = $reverb_inventory - $shopifyProduct['inventory_quantity'];

    dump("adjustmentQuantity : " . $adjustmentQuantity);
    if($adjustmentQuantity != 0){
        $shopify->update_inventory($shopifyProduct['inventory_item_id'], $adjustmentQuantity);
    }


});


Route::view('/', 'welcome');

Route::get('/process-orders', function () {
    Artisan::call('process:reverb-orders');
});

Route::get('/fetch-products', function () {
    Artisan::call('fetch:shopify-products');
});

Route::get('/test', function () {
    $result = 'This is just test page' . time();
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