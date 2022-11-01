<?php

namespace App\Modules;

use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Omnipay;

class PayPal
{
    /**
     * Create gateway instance.
     *
     * @return GatewayInterface
     */
    public function gateway(): GatewayInterface
    {
        $gateway = Omnipay::create('PayPal_Rest');

        $gateway->setClientId(config('paypal.client_id'));
        $gateway->setSecret(config('paypal.secret'));
        $gateway->setTestMode(config('paypal.mode'));

        return $gateway;
    }

    /**
     * Send purchase.
     *
     * @param array $parameters
     * @return ResponseInterface
     */
    public function purchase(array $parameters): ResponseInterface
    {
        return $this->gateway()
            ->purchase($parameters)
            ->send();
    }

    /**
     * Send complete purchase.
     *
     * @param string $payer_id
     * @param string $transactionReference
     * @return ResponseInterface
     */
    public function complete(string $payer_id, string $transactionReference): ResponseInterface
    {
        return $this->gateway()
            ->completePurchase(compact('payer_id', 'transactionReference'))
            ->send();
    }
}
