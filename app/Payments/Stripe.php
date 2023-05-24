<?php

namespace App\Payments;

use Exception;
use phpDocumentor\Reflection\Types\Integer;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

class Stripe
{
    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));;
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
    public  function updatePaymentMethod_Card(string $payment_method, array $card_data)
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
    public static function detachPaymentMethod($payment_method)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->detach(
            $payment_method,
            []
        );

        return $response;
    }

    /**
     * Create a product.
     *
     * @param $name
     * @param $description
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createProduct($name, $description)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->products->create([
            'name' => $name,
            'description' => $description,
        ]);

        return $response;
    }

    /**
     * Update a product.
     *
     * @param $product_id
     * @param $name
     * @param $description
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function updateProduct($product_id, $name, $description)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->products->update(
            $product_id,
            [
                'name' => $name,
                'description' => $description,
            ]
        );

        return $response;
    }

    /**
     * Delete a product.
     *
     * @param $product_id
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function deleteProduct($product_id)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->products->delete($product_id, []);

        return $response;
    }

    /**
     * Create price.
     *
     * @param Integer $amount
     * @param $product_id
     * @return \Stripe\Price
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createPrice(integer $amount, $product_id)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->prices->create([
            'unit_amount' => $amount,
            'currency' => 'usd',
            'product' => $product_id,
        ]);

        return $response;
    }

    /**
     * Update a price.
     *
     * @param $price_id
     * @param $amount
     * @param $product_id
     * @return \Stripe\Price
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function updatePrice($price_id, $amount, $product_id)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->prices->update(
            $price_id,
            [
                'unit_amount' => $amount,
                'product' => $product_id,
            ]
        );

        return $response;
    }
}
