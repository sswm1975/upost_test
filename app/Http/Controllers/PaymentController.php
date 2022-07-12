<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use PayPal\Api\Plan;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\PayerInfo;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use Redirect;
use URL;

class PaymentController extends Controller
{
    private ApiContext $_api_context;

    public function __construct()
    {
        /** PayPal api context **/
        $paypal_conf = \Config::get('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret'])
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }

    public function payWithPaypal()
    {
        $amountToBePaid = 100;
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item_1 = new Item();
        $item_1->setName('Mobile Payment') /** название элемента **/
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($amountToBePaid); /** цена **/

        $item_list = new ItemList();
        $item_list->setItems(array($item_1));

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($amountToBePaid);

        $redirect_urls = new RedirectUrls();
        /** Укажите обратный URL **/
        $redirect_urls->setReturnUrl(URL::route('status'))
            ->setCancelUrl(URL::route('status'));

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Описание транзакции');

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            if (\Config::get('app.debug')) {
                Session::put('error', 'Таймаут соединения');
                return Redirect::route('/');
            } else {
                Session::put('error', 'Возникла ошибка, извините за неудобство');
                return Redirect::route('/');
            }
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        /** добавляем ID платежа в сессию **/
        Session::put('paypal_payment_id', $payment->getId());

        if (isset($redirect_url)) {
            /** редиректим в paypal **/
            return Redirect::away($redirect_url);
        }

        Session::put('error', 'Произошла неизвестная ошибка');
        return Redirect::route('/');
    }

    public function getPaymentStatus(Request $request)
    {
        /** Получаем ID платежа до очистки сессии **/
        $payment_id = Session::get('paypal_payment_id');
        /** Очищаем ID платежа **/
        Session::forget('paypal_payment_id');

        if (empty($request->PayerID) || empty($request->token)) {
            session()->flash('error', 'Payment failed');
            return Redirect::route('/');
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);

        /** Выполняем платёж **/
        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() == 'approved') {
            session()->flash('success', 'Платеж прошел успешно');
            return Redirect::route('/');
        }

        session()->flash('error', 'Платеж не прошел');
        return Redirect::route('/');
    }
}
