<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Omnipay\Common\GatewayInterface;
use Omnipay\Omnipay;

class PaymentController extends Controller
{
    public GatewayInterface $gateway;

    public function __construct()
    {
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(config('paypal.client_id'));
        $this->gateway->setSecret(config('paypal.secret'));
        $this->gateway->setTestMode(true);
    }

    public function index()
    {
        return view('payment');
    }

    public function charge(Request $request)
    {
        if ($request->input('submit')) {
            try {
                $response = $this->gateway->purchase([
                    'amount' => $request->input('amount'),
                    'currency' => 'USD',
                    'items' => [
                        [
                            'name' => 'Ноутбук',
                            'price' => $request->input('amount'),
                            'description' => 'Хочу такой ноут',
                            'quantity' => 1
                        ],
                    ],
                    'returnUrl' => url('payment_success'),
                    'cancelUrl' => url('payment_error'),
                ])->send();

                if ($response->isRedirect()) {
                    // перенаправление на сторонний платежный шлюз
                    $response->redirect();
                } else {
                    // Платеж не прошел
                    \Log::error('Not successful: ' . $response->getMessage());
                    return $response->getMessage();
                }
            } catch(\Exception $e) {
                \Log::error('Exception: ' . $e->getMessage());
                return $e->getMessage();
            }
        }
    }

    public function payment_success(Request $request): ?string
    {
        \Log::info($request->all());

        // Once the transaction has been approved, we need to complete it.
        if ($request->input('paymentId') && $request->input('PayerID')) {
            $transaction = $this->gateway->completePurchase([
                'payer_id'             => $request->input('PayerID'),
                'transactionReference' => $request->input('paymentId'),
            ]);
            $response = $transaction->send();

            if ($response->isSuccessful()) {
                // The customer has successfully paid.
                $arr_body = $response->getData();
                \Log::debug($arr_body);

/*
                // Insert transaction data into the database
                $isPaymentExist = Payment::where('payment_id', $arr_body['id'])->first();

                if(!$isPaymentExist)
                {
                    $payment = new Payment;
                    $payment->payment_id = $arr_body['id'];
                    $payment->payer_id = $arr_body['payer']['payer_info']['payer_id'];
                    $payment->payer_email = $arr_body['payer']['payer_info']['email'];
                    $payment->amount = $arr_body['transactions'][0]['amount']['total'];
                    $payment->currency = env('PAYPAL_CURRENCY');
                    $payment->payment_status = $arr_body['state'];
                    $payment->save();
                }
*/

                return "Оплата прошла успешно. Идентификатор вашей транзакции: ". $arr_body['id'];
            } else {
                return $response->getMessage();
            }
        } else {
            return 'Транзакция отклонена';
        }
    }

    public function payment_error(): string
    {
        return 'Пользователь отменил платеж.';
    }
}
