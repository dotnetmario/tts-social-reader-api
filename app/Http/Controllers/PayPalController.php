<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Subscription;

class PayPalController extends BaseController
{

    /**
     * Create PayPal products for local subscription plans and associate them.
     *
     * Loops through predefined subscription plans and registers them as products on PayPal,
     * storing the resulting PayPal product IDs back to local subscriptions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

            // Register the product with PayPal
            $productResponse = $provider->createProduct([
                'name' => $product['name'],
                'description' => $product['description'],
                'type' => $product['type'],
                'category' => $product['category'],
            ]);

            // Save PayPal product ID locally
            $subscription->paypal_plan_id = $productResponse['id'];
            $subscription->save();

            $paypal_product_ids[$product['local_subscription_id']] = $productResponse['id'];
        }

        return $this->sendSuccessResponse([
            'products' => $paypal_product_ids
        ]);
    }


    /**
     * Create a PayPal order and redirect the user to PayPal approval page.
     *
     * Based on the requested subscription ID, it initializes an order through PayPal
     * and redirects the user to PayPal's checkout.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrder(Request $request)
    {
        // for web/browser view
        $request->validate([
            'subscription_id' => 'required|numeric'
        ]);
        // for API 
        // $this->validateRequest($request, [
        //     'subscription_id' => 'required|numeric'
        // ]);

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

    /**
     * Handle PayPal payment success and allocate credits to the user.
     *
     * Captures the PayPal payment order and, upon success,
     * grants the user characters based on the subscription.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function paymentSuccess(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->token);

        if ($response['status'] === 'COMPLETED') {
            // $user = auth()->user();
            // TEMP: Hardcoded user. Replace with `auth()->user()` in production.
            $user = User::find(2);
            $subscription = Subscription::findOrFail($request->subscription_id);

            // Allocate credits to user account
            Credit::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'characters' => $subscription->characters,
                'characters_used' => 0,
                'expires_at' => now()->addYear()
            ]);

            return redirect()->route('dashboard')->with('success', 'Payment successful. Credits added.');
        }

        return redirect()->route('dashboard')->with('error', 'Payment failed.');
    }
}
