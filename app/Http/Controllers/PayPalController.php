<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Subscription;

class PayPalController extends BaseController
{
    public function createProduct()
    {
        $paypal_product_ids = [];
        $products_list = [
            [
                'local_subscription_id' => 1,
                'name' => 'Basic Plan',
                'description' => 'a basic plan with 1M characters!!',
                'type' => 'SERVICE', // Use 'SERVICE' for software/SaaS
                'category' => 'SOFTWARE',
            ]
        ];

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();


        // create products on the Paypal platform
        foreach ($products_list as $product) {
            // look up the local subscription
            $subscription = Subscription::find((int) $product['local_subscription_id']);

            if (empty($subscription->id)) {
                continue;
            }


            $productResponse = $provider->createProduct([
                'name' => $product['name'],
                'description' => $product['description'],
                'type' => $product['type'],
                'category' => $product['category'],
            ]);

            $subscription->paypal_plan_id = $productResponse['id'];
            $subscription->save();

            $paypal_product_ids[$product['local_subscription_id']] = $productResponse['id'];
        }

        return $this->sendSuccessResponse([
            'products' => $paypal_product_ids
        ]);
    }


    public function createOrder(Request $request)
    {
        // for web/browser view
        // $request->validate([
        //     'subscription_id' => 'requried|numeric'
        // ]);
        // for API 
        $this->validateRequest($request, [
            'subscription_id' => 'requried|numeric'
        ]);

        $subscription = Subscription::findOrFail($request->subscription_id);

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $order = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.success', ['subscription_id' => $subscription->id]),
                "cancel_url" => route('paypal.cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $subscription->price,
                    ],
                    "description" => $subscription->name
                ]
            ]
        ]);

        if (isset($order['id']) && $order['status'] === 'CREATED') {
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }
        }

        return redirect()->route('dashboard')->with('error', 'Unable to create PayPal order.');
    }

    public function paymentSuccess(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->token);

        if ($response['status'] === 'COMPLETED') {
            $user = auth()->user();
            $subscription = Subscription::findOrFail($request->subscription_id);

            $user->credits()->create([
                'subscription_id' => $subscription->id,
                'characters' => $subscription->characters,
                'characters_used' => 0,
                'expires_at' => now()->addYear(),
            ]);

            return redirect()->route('dashboard')->with('success', 'Payment successful. Credits added.');
        }

        return redirect()->route('dashboard')->with('error', 'Payment failed.');
    }
}
