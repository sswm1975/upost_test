<?php

namespace App\Payments;

use phpDocumentor\Reflection\Types\Integer;
use Stripe\StripeClient;

class Stripe
{
    /**
     * Create a customer.
     * https://stripe.com/docs/api/customers/create?lang=php
     *
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return \Stripe\Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createCustomer(string $name, string $email = '', string $phone = '')
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->customers->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone ,
        ]);

        return $response;
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
    public static function updateCustomer(string $customer_id, string $name, string $email = '', string $phone = '')
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->customers->update(
            $customer_id,
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone ,
            ]
        );

        return $response;
    }

    /**
     * Create a PaymentMethod.
     * https://stripe.com/docs/api/payment_methods/create?lang=php
     *
     * @param string $number
     * @param string $exp_month
     * @param string $exp_year
     * @param string $cvc
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createPaymentMethod_Card(string $number, string $exp_month, string $exp_year, string $cvc)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => $number,
                'exp_month' => $exp_month,
                'exp_year' => $exp_year,
                'cvc' => $cvc,
            ],
        ]);

        return $response;
    }

    /**
     * Update a PaymentMethod.
     * https://stripe.com/docs/api/payment_methods/update?lang=php
     *
     * @param string $payment_method
     * @param string $number
     * @param string $exp_month
     * @param string $exp_year
     * @param string $cvc
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function updatePaymentMethod_Card(string $payment_method, string $number, string $exp_month, string $exp_year, string $cvc)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->update(
            $payment_method,
            [
                'type' => 'card',
                'card' => [
                    'number' => $number,
                    'exp_month' => $exp_month,
                    'exp_year' => $exp_year,
                    'cvc' => $cvc,
                ],
            ]
        );

        return $response;
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
    public static function attachPaymentMethod($payment_method, $customer_id)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->attach(
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
