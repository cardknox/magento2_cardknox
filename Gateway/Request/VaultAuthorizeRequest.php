<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class VaultAuthorizeRequest implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $amount = $this->formatPrice($buildSubject['amount']);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        return [
            'xAmount' => $amount,
            'xToken' => $paymentToken->getGatewayToken(),
            'xCommand' => 'cc:authonly',
            'xInvoice' => $order->getOrderIncrementId(),
            'xCurrency' => $order->getCurrencyCode(),
            'xIgnoreInvoice' => true,
            'xTimeoutSeconds' => 55,
            'xAllowDuplicate' => true
        ];
    }

    /**
     * Format price to 0.00 format
     *
     * @param mixed $price
     * @return string
     * @since 100.1.0
     */
    public function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }
}
