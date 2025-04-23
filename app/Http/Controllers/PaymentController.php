<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Omnipay\Omnipay;
use App\Models\Order;
use App\Models\Payment;

class PaymentController extends Controller
{
    private $gateway;

    public function __construct(){
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(env('PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(env('PAYPAL_CLIENT_SECRET'));
        $this->gateway->setTestMode(true);
    }

    public function pay(Request $request)
{
    try {
       
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        $response = $this->gateway->purchase([
            'amount' => $order->total_amount,
            'currency' => env("PAYPAL_CURRENCY"),
            'returnUrl' => url('success?order_id=' . $order->id),
            'cancelUrl' => url('error'),
        ])->send();

        if ($response->isRedirect()) {
            return $response->redirect();
        } else {
            return $response->getMessage();
        }

    } catch (\Throwable $th) {
        return $th->getMessage();
    }
}

public function success(Request $request)
{
    if ($request->input('paymentId') && $request->input('PayerID')) {
        $transaction = $this->gateway->completePurchase([
            'payer_id' => $request->input('PayerID'),
            'transactionReference' => $request->input('paymentId')
        ]);

        $response = $transaction->send();

        if ($response->isSuccessful()) {
            $data = $response->getData();

            
            $orderId = $request->input('order_id');
            $order = Order::findOrFail($orderId);

            
            Payment::create([
                'payment_id' => $data['id'],
                'payer_id' => $data['payer']['payer_info']['payer_id'],
                'payer_email' => $data['payer']['payer_info']['email'],
                'amount' => $order->total_amount,
                'currency' => $data['transactions'][0]['amount']['currency'],
                'payment_status' => $data['state'],
                'order_id' => $order->id,
            ]);

            return "Payment is successful. Transaction ID: " . $data['id'];
        } else {
            return $response->getMessage();
        }
    } else {
            return "Payment is declined";
        }
    }
    
        public function error(){
        return "User declined the payment";
    }
}
