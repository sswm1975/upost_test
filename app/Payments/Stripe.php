<?php

namespace App\Payments;

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
     * @param array $card
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createPaymentMethod($card = [])
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => $card['number'],
                'exp_month' => $card['exp_month'],
                'exp_year' => $card['exp_year'],
                'cvc' => $card['cvc'],
            ],
        ]);

        return $response;
    }

    /**
     * Update a PaymentMethod.
     * https://stripe.com/docs/api/payment_methods/update?lang=php
     *
     * @param $payment_method
     * @param array $card
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function updatePaymentMethod($payment_method, $card = [])
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $response = $stripe->paymentMethods->update(
            $payment_method,
            [
                'type' => 'card',
                'card' => [
                    'number' => $card['number'],
                    'exp_month' => $card['exp_month'],
                    'exp_year' => $card['exp_year'],
                    'cvc' => $card['cvc'],
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
}
