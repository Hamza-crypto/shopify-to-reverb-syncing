<?php

use App\Http\Controllers\WebhookController;
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

Route::get('/test', function () {
    $result = "This is just test page" . time();
    echo $result;
    DiscordAlert::message($result);
});

Route::controller(WebhookController::class)->group(function () {
    Route::post('shopify-new-order', 'shopify_new_order'); // Shopify New Order

    Route::get('get_product', 'get_shopify_product_inventory_test');

    Route::post('get_etsy_code', 'get_etsy_code'); // Get Etsy Code

});
