<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use App\Models\ProductMapping;
use App\Models\WebshippyOrders;
use App\Notifications\LeadVertexNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class WebhookController extends Controller
{
    public function shopify_new_order(Request $request)
    {
        /*
         * This function get webhook notification from Shopify when new order is created
         */
        $data = $request->all();

        app('log')->channel('shopify')->info($data);
        dd($data);

        if ($data['status'] != 'accepted') {
            return;
        }

        dump($data);
        $data_array['to'] = 'webshippy';
        $data_array['msg'] = sprintf("Leadvertex order no. %s status updated to ACCEPTED", $data['id']);

        try {
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        } catch (\Exception $e) {
        }

        $url = sprintf("%s/getOrdersByIds.html?token=%s&ids=%d", env('LEADVERTEX_API_URL'), env('TOKEN'), $data['id']);

        try {
            $response = Http::get($url);
            $response = json_decode($response);

            // This job is now done by telescope
//            ProductWebhook::create([
//                'product_id' => $data['id'],
//                'response' => $response
//            ]);

//            $json = file_get_contents(public_path('vertex.json'));
//            $response = json_decode($json);

            $subtotal = 0;
            $total_number_of_products = 0;
            foreach ($response as $order) {
                $products = [];

                foreach ($order->goods as $product) {
                    $product_sku = ProductMapping::where('product_id_lv', $product->goodID)->first();
                    if (!$product_sku) {
                        continue;
                    }

                    $products[] = [
                        'sku' => $product_sku->webshippy_sku,
                        'productName' => $product->name,
                        'priceGross' => $product->price,
                        'vat' => 0.27,
                        'quantity' => $product->quantity,
                    ];
                    $subtotal += $product->price * $product->quantity;
                    $total_number_of_products += $product->quantity;
                }

                if ($total_number_of_products > 2) {
                    $shippingPrice = 0;
                } elseif ($total_number_of_products == 2) {
                    $shippingPrice = 1500;
                } elseif ($total_number_of_products == 1) {
                    $shippingPrice = 3500;
                }

                $phoneNumber = $order->phone ?? '';
                if (strpos($phoneNumber, '36') === 0) {
                    $phoneNumber = '+' . $phoneNumber;
                }

                $request_body = [
                    'apiKey' => env('TOKEN'),
                    'order' => [
                        'referenceId' => "LV#" . $data['id'],
                        'createdAt' => $order->datetime,
                        'shipping' => [
                            'name' => $order->fio,
                            'email' => $order->email,
                            'phone' => $phoneNumber,
                            'countryCode' => $order->country,
                            'zip' => $order->postIndex,
                            'city' => $order->city,
                            'country' => $order->country,
                            'address1' => $order->address,
                            'note' => $order->comment,
                        ],
                        'billing' => [
                            'name' => $order->fio,
                            'email' => $order->email,
                            'phone' => $phoneNumber,
                            'countryCode' => $order->country,
                            'zip' => $order->postIndex,
                            'city' => $order->city,
                            'country' => $order->country,
                            'address1' => $order->address,

                        ],
                        'payment' => [
                            'paymentMode' => "cod",
                            'codAmount' => $subtotal,
                            'paymentStatus' => "pending",
                            'paidDate' => $order->lastUpdate,
                            "shippingPrice" => $shippingPrice,
                            'shippingVat' => 0,
                            'currency' => "HUF",
                            'discount' => 0,
                        ],
                        'products' => $products,
                    ],
                ];

                $request_body['order']['payment']['shippingPrice'] = $shippingPrice; //if quantity > 2, then set shipping price to 0
                $request_body['order']['payment']['codAmount'] += $shippingPrice;

                $url = sprintf("%s/CreateOrder/json", env('WEBSHIPPY_API_URL'));
                $request_body = ['request' => json_encode($request_body)];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->asForm()->post($url, $request_body);

                dump($response->json());

                app('log')->channel('webhooks')->info($response->json());

                $response = json_decode($response);
                WebshippyOrders::updateOrCreate([
                    'order_id' => $response->wspyId,
                ],
                    ['status' => 'new']
                );

                $data_array['msg'] = sprintf("Webshippy new order: %s", $response->wspyId);
                try {
                    Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
                } catch (\Exception $e) {
                }

                return $response;
            }
        } catch (\Exception $e) {
            $data_array['msg'] = $e->getMessage() . " WebhookController line 145";
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        }

    }

    public function createRecordOnComnica(Request $request)
    {
        $data = $request->all();
        $msg = "";

        $data_array['to'] = 'comnica';
        $data_array['msg'] = "Leadvertex new order: " . $data['id'] . " ";
        Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        app('log')->channel('webhooks')->info($data);

        $url = sprintf("%s/getOrdersByIds.html?token=%s&ids=%d", env('LEADVERTEX_API_URL'), env('TOKEN'), $data['id']);

        $response = Http::get($url);
        // $response = file_get_contents(public_path('vertex.json'));

        $response = json_decode($response);

        foreach ($response as $order) {
            $name = $order->fio;
            $phone = $order->phone;
            $productName = "";

            $isBlocked = BlockedUser::where('phone', $phone)->first();

            if ($isBlocked) {
                $response_id = $this->mark_as_spam_on_leadvertex($data['id']);

                if ($response_id == "OK") {
                    $data_array['to'] = 'comnica';
                    $data_array['msg'] = sprintf("Order %s marked as spam on Leadvertex: ", $data['id']);
                    Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
                    return 0;
                }

            }

            foreach ($order->goods as $product) {
                $productName .= $product->name . ',';
            }

        }

        $this->sendData($name, $phone, $productName, $data['id'], $order->datetime, $msg);

    }

    public function sendData($name, $phone, $productName, $id, $date, $msg)
    {

        $data_array['to'] = 'comnica';

        if (strlen($phone) == 9) {
            $phone = "36" . $phone;
        }

        //If starting from 0, then append 3 at the begining
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }

        if (substr($phone, 0, 1) === "0") {
            $phone = "3" . substr($phone, 1);
        }

        $msg .= "Phone: ";
        $msg .= $phone;
        $msg .= " ";
        $result = $msg;

        $data = [
            'rq_sent' => '',
            'payload' => [
                'comments' => [],
                'contacts' => [
                    [
                        'active' => true,
                        'contact' => $phone,
                        'name' => '',
                        'preferred' => true,
                        'priority' => 1,
                        'source_column' => 'phone',
                        'type' => 'phone',
                    ],
                ],
                'custom_data' => [
                    'name' => $name,
                    'phone' => $phone,
                    'termek' => $productName,
                    'sp_id' => $id,
                    'date' => $date,
                ],
                'system_columns' => [
                    'callback_to_user_id' => null,
                    'dial_from' => null,
                    'dial_to' => null,
                    'manual_redial' => null,
                    'next_call' => null,
                    'priority' => 1,
                    'project_id' => 76,
                ],
            ],
        ];

        $response = Http::withBasicAuth(env('COMNICA_USER'), env('COMNICA_PASS'))->post(env('COMNICA_API_URL') . '/integration/cc/record/save/v1', $data);
        //$response = file_get_contents(public_path('comnica.json'));

        $main_response = json_decode($response);
        #run loop on response->json and create string for each array element

        if (isset($main_response->payload->errors)) {

            $responseArray = json_decode($response, true);
            foreach ($responseArray as $key => $value) {
                $result .= $key . ': ' . (is_array($value) ? json_encode($value) : $value) . ', ';
            }

            $result = rtrim($result, ', ');

            $result = substr($result, 0, 2000);
        } else {
            $result .= " Comnica ID: ";
            $result .= $main_response->payload->id;
        }

        $data_array['msg'] = $result;
        Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        // DiscordAlert::message($result);

    }

    public function mark_as_spam_on_leadvertex($lead_vertex_id)
    {
        $url = sprintf("%s/updateOrder.html?token=%s&id=%d", env('LEADVERTEX_API_URL'), env('TOKEN'), $lead_vertex_id);

        $request_body = [
            'status' => 9, // Spam/Errors
        ];
        $lv_response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post($url, $request_body);

        $lv_response = json_decode($lv_response);

        return $lv_response->$lead_vertex_id;
    }

}
