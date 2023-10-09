<?php

namespace App\Payments;

use App\Models\StripeLog;
use Exception;
use Stripe\StripeClient;

class Stripe
{
    const CUSTOMER_CREATE = 'customer_create';
    const CUSTOMER_UPDATE = 'customer_update';
    const PRODUCT_CREATE = 'product_create';
    const PRODUCT_UPDATE = 'product_update';
    const PRODUCT_DELETE = 'product_delete';
    const PRICE_CREATE = 'price_create';
    const CHECKOUT_SESSIONS_CREATE = 'checkout_sessions_create';
    const CHECKOUT_SESSIONS_RETRIEVE = 'checkout_sessions_retrieve';
    const REFUND_CREATE = 'refund_create';
    const PAYOUT_CREATE = 'payout_create';

    private $stripe;

    /**
     * Init Stripe Client.
     */
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a customer.
     * @link https://stripe.com/docs/api/customers/create?lang=php
     *
     * @param array $params
     * @return array|\Stripe\Customer
     */
    public function createCustomer(array $params)
    {
        $customer = null;
        $is_error = false;

        try {
            $customer = $this->stripe->customers->create([
                'name'  => $params['name'],
                'email' => $params['email'],
                'phone' => $params['phone'],
            ]);
        } catch (Exception $e) {
            $customer['error'] = $e->getMessage();
            $is_error = true;
        }

        StripeLog::add(self::CUSTOMER_CREATE, $params, $customer,$is_error);

        return $customer;
    }

    /**
     * Update a customer.
     * @link https://stripe.com/docs/api/customers/update?lang=php
     *
     * @param string $customer_id
     * @param array $params
     * @return \Stripe\Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updateCustomer(string $customer_id, array $params)
    {
        $customer = null;
        $is_error = false;

        try {
            $customer = $this->stripe->customers->update($customer_id, $params);
        } catch (Exception $e) {
            $customer['error'] = $e->getMessage();
            $is_error = true;
        }

        StripeLog::add(self::CUSTOMER_UPDATE, $params, $customer, $is_error);

        return $customer;
    }

    /**
     * Create a product.
     *
     * @param array $data
     * @return array|\Stripe\Product
     */
    public function createProduct(array $data)
    {
        $response = null;

        try {
            $response = $this->stripe->products->create([
                'name' => $data['name'],
            ]);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::PRODUCT_CREATE, $data, $response, isset($response['error']));

        return $response;
    }

    /**
     * Update a product.
     *
     * @param string $product
     * @param array $params
     * @return array|\Stripe\Product
     */
    public function updateProduct(string $product, array $params)
    {
        $response = null;

        try {
            $response = $this->stripe->products->update(
                $product,
                [
                    'name' => $params['name'],
                ]
            );
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::PRODUCT_UPDATE, array_merge(compact('product'), $params), $response, isset($response['error']));

        return $response;
    }

    /**
     * Delete a product.
     *
     * @param $product
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function deleteProduct($product)
    {
        $response = null;

        try {
            $response = $this->stripe->products->delete($product, []);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::PRODUCT_DELETE, compact('product'), $response, isset($response['error']));

        return $response;
    }

    /**
     * Create price.
     *
     * @param $product_id
     * @param $amount
     * @param $rate_id
     * @return array|\Stripe\Price
     */
    public function createPrice($product, $amount, $rate_id)
    {
        $response = null;

        try {
            $response = $this->stripe->prices->create([
                'product' => $product,
                'unit_amount' => $amount,
                'currency' => 'usd',
                'tax_behavior' => 'exclusive',
                'metadata' => [
                    'rate_id' => $rate_id,
                ],
            ]);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::PRICE_CREATE, compact('product', 'amount', 'rate_id'), $response, isset($response['error']));

        return $response;
    }

    /**
     * Создать checkout sessions платеж на оплату по ссылке.
     *
     * @param $params
     * @return array|\Stripe\Checkout\Session
     */
    public function createCheckout($params)
    {
        try {
            $response = $this->stripe->checkout->sessions->create([
                'payment_intent_data' => ['setup_future_usage' => 'off_session'],
                'line_items' => [[
                    'price' => $params['price_id'],
                    'quantity' => 1,
                ]],
                'customer' => $params['customer_id'],
                'mode' => 'payment',
                'client_reference_id' => $params['transaction_id'],
                'success_url' => $params['purchase_success_url'],
                'cancel_url' => $params['purchase_error_url'],
                'automatic_tax' => [
                    'enabled' => false,
                ],
            ]);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::CHECKOUT_SESSIONS_CREATE, $params, $response, isset($response['error']));

        return $response;
    }

    /**
     * Получить детальную информацию об оплате.
     *
     * @param $checkout_session
     * @return \Stripe\Checkout\Session
     */
    public function retrieveCheckout($checkout_session)
    {
        try {
            $response = $this->stripe->checkout->sessions->retrieve($checkout_session, []);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::CHECKOUT_SESSIONS_RETRIEVE, compact('checkout_session'), $response, isset($response['error']));

        return $response;
    }

    /**
     * Create a refund.
     * @link https://stripe.com/docs/api/refunds/create
     *
     * @param $charge
     * @return array|\Stripe\Refund
     */
    public function createRefund($payment_intent)
    {
        try {
            $response = $this->stripe->refunds->create(compact('payment_intent'));
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::REFUND_CREATE, compact('payment_intent'), $response, isset($response['error']));

        return $response;
    }

    /**
     * Create a payout.
     * @link https://stripe.com/docs/api/payouts/create
     * @param $amount
     * @param $description
     *
     * @return \Stripe\Payout
     */
    public function createPayout($amount, $description)
    {
        $params = [
            'amount'      => $amount,
            'currency'    => 'usd',
            'description' => $description,
        ];
        try {
            $response = $this->stripe->payouts->create($params);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        StripeLog::add(self::PAYOUT_CREATE, $params, $response, isset($response['error']));

        return $response;
    }

    /**
     * Retrieve balance.
     * @link https://stripe.com/docs/api/balance/balance_retrieve
     * @return array|\Stripe\Balance
     */
    public function getBalance()
    {
        try {
            $response = $this->stripe->balance->retrieve([]);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }


    /**
     * Расчет суммы, которую нужно передавать при Stripe-оплате.
     *
     * @param $amount
     * @return float
     */
    public function calculatePrice($amount)
    {
        $commissionRate = 0.029;   // Комісійна ставка (2.9%)
        $commissionFee = 0.30;     // Фіксована комісія ($0.30)

        $price = ($amount + $commissionFee) / (1 - $commissionRate);

        return round($price, 2);
    }
}
