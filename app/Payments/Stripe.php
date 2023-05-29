<?php

namespace App\Payments;

use Exception;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

class Stripe
{
    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a customer.
     * https://stripe.com/docs/api/customers/create?lang=php
     *
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return array|\Stripe\Customer
     */
    public function createCustomer(string $name, string $email = '', string $phone = '')
    {
        $customer = null;

        try {
            $customer = $this->stripe->customers->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone ,
            ]);
        } catch (Exception $e) {
            $customer['error'] = $e->getMessage();
        }

        return $customer;
    }

    /**
     * Update a customer.
     * https://stripe.com/docs/api/customers/update?lang=php
     *
     * @param string $customer_id
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return \Stripe\Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updateCustomer(string $customer_id, string $name, string $email = '', string $phone = '')
    {
        $customer = null;

        try {
            $customer = $this->stripe->customers->update(
                $customer_id,
                [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone ,
                ]
            );
        } catch (Exception $e) {
            $customer['error'] = $e->getMessage();
        }

        return $customer;
    }

    /**
     * Create a PaymentMethod.
     * https://stripe.com/docs/api/payment_methods/create?lang=php
     *
     * @param array $card_data
     * @return array|\Stripe\PaymentMethod
     */
    public function createPaymentMethod_Card($card_data)
    {
        $card = null;

        try {
            $card = $this->stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $card_data['card_number'],
                    'exp_month' => $card_data['card_exp_month'],
                    'exp_year' => $card_data['card_exp_year'],
                    'cvc' => $card_data['card_cvc'],
                ],
            ]);
        } catch (CardException $e) {
            $card['error'] = $e->getError()->message;
        } catch (Exception $e) {
            $card['error'] = $e->getMessage();
        }

        return $card;
    }

    /**
     * Update a PaymentMethod.
     * https://stripe.com/docs/api/payment_methods/update?lang=php
     *
     * @param string $payment_method
     * @param array $card_data
     * @return array|\Stripe\PaymentMethod
     */
    public function updatePaymentMethod_Card(string $payment_method, array $card_data)
    {
        $card = null;

        try {
            $card = $this->stripe->paymentMethods->update(
                $payment_method,
                [
                    'type' => 'card',
                    'card' => [
                        'number' => $card_data['card_number'],
                        'exp_month' => $card_data['card_exp_month'],
                        'exp_year' => $card_data['card_exp_year'],
                        'cvc' => $card_data['card_cvc'],
                    ],
                ]
            );
        } catch (CardException $e) {
            $card['error'] = $e->getError()->message;
        } catch (Exception $e) {
            $card['error'] = $e->getMessage();
        }

        return $card;
    }

    /**
     * Attach a PaymentMethod to a Customer.
     * https://stripe.com/docs/api/payment_methods/attach?lang=php
     *
     * @param $payment_method
     * @param $customer_id
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function attachPaymentMethod($payment_method, $customer_id)
    {
        $response = $this->stripe->paymentMethods->attach(
            $payment_method,
            ['customer' => $customer_id]
        );

        return $response;
    }

    /**
     * Detach a PaymentMethod from a Customer.
     *
     * @param $payment_method
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function detachPaymentMethod($payment_method)
    {
        $response = $this->stripe->paymentMethods->detach(
            $payment_method,
            []
        );

        return $response;
    }

    /**
     * Create a product.
     *
     * @param array $data
     * @return array|\Stripe\Product
     */
    public function createProduct(array $data)
    {
        $product = null;

        try {
            $product = $this->stripe->products->create([
                'name' => $data['name'],
            ]);
        } catch (Exception $e) {
            $product['error'] = $e->getMessage();
        }

        return $product;
    }

    /**
     * Update a product.
     *
     * @param string $product_id
     * @param array $data
     * @return array|\Stripe\Product
     */
    public function updateProduct(string $product_id, array $data)
    {
        $product = null;

        try {
            $product = $this->stripe->products->update(
                $product_id,
                [
                    'name' => $data['name'],
                ]
            );
        } catch (Exception $e) {
            $product['error'] = $e->getMessage();
        }

        return $product;
    }

    /**
     * Delete a product.
     *
     * @param $product_id
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function deleteProduct($product_id)
    {
        $response = $this->stripe->products->delete($product_id, []);

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
    public function createPrice($product_id, $amount, $rate_id)
    {
        $price = null;

        try {
            $price = $this->stripe->prices->create([
                'product' => $product_id,
                'unit_amount' => $amount,
                'currency' => 'usd',
                'metadata' => [
                    'rate_id' => $rate_id,
                ],
            ]);
        } catch (Exception $e) {
            $price['error'] = $e->getMessage();
        }

        return $price;
    }

    /**
     * !!! Не использовать - выдает ошибки
     * Update a price.
     *
     * @param $price_id
     * @param $amount
     * @return \Stripe\Price
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updatePrice($price_id, $amount)
    {
        $price = null;

        try {
            $price = $this->stripe->prices->update(
                $price_id,
                [
                    'currency_options' => [
                        'usd' => [
                            'unit_amount' => $amount
                        ]
                    ]
                ]
            );
        } catch (Exception $e) {
            $price['error'] = $e->getMessage();
        }

        return $price;
    }

    public function createCheckout($data)
    {
        $checkout_session = null;

        try {
            $checkout_session = $this->stripe->checkout->sessions->create([
                'line_items' => [[
                    'price' => $data['price_id'],
                    'quantity' => 1,
                ]],
                'customer' => $data['customer_id'],
                'mode' => 'payment',
                'client_reference_id' => $data['transaction_id'],
                'success_url' => $data['purchase_success_url'],
                'cancel_url' => $data['purchase_error_url'],
                'automatic_tax' => [
                    'enabled' => false,
                ],
            ]);
        } catch (Exception $e) {
            $checkout_session['error'] = $e->getMessage();
        }

        return $checkout_session;
    }
}
