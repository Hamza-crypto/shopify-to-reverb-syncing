<?php

use App\Http\Controllers\ChartController;
use App\Http\Controllers\ReverbController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use App\Models\Product;
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

Route::view('/', 'welcome');

// Route::get('/process-orders', function () {
//     Artisan::call('process:reverb-orders');
// });

// Route::get('/fetch-products', function () {
//     Artisan::call('fetch:shopify-products');
// });

Route::get('/test', function () {
    $pro = Product::where('id', 4)->first();
    $pro = $pro->full_data;
    dd($pro);
});

Route::get('/migrate/fresh', function () {
    Artisan::call('migrate:fresh --seed');
});

Route::get('/fetch_shopify_products', function () {
    Artisan::call('fetch:shopify-products');
});


Route::prefix('create_shopify_listing')->controller(ReverbController::class)->group(function () {
    Route::get('/', 'create_listing');

});

Route::prefix('shopify')->controller(WebhookController::class)->group(function () {
    Route::post('new_order', 'shopify_new_order'); // webhook when new order is placed on shopify
    Route::post('product_updated', 'shopify_product_updated'); //webhook when product inventory quantity is updated  in shopify admin dashboard
    // Route::post('product_added', 'shopE:\Installed\laragon\www\shopify-to-reverb-syncingify_new_product_added'); //webhook when new product is added into shopify.
});

Route::controller(ChartController::class)->group(function () {
    Route::get('income-chart', 'index');
    Route::post('income-chart', 'store')->name('chart.store');
    Route::get('chart-data', 'chart_data')->name('chart.data');
});
//
