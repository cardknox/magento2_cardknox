<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
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
        $cc_exp_month = $payment->getAdditionalInformation("cc_exp_month");
        $cc_exp_year = $payment->getAdditionalInformation("cc_exp_year");
        return [
            'xAmount' => $amount,
            'xExp' => sprintf('%02d%02d', $cc_exp_month, substr($cc_exp_year, -2)),
            'xCVV' => $payment->getAdditionalInformation("xCVV"),
            'xCommand' => 'cc:authonly',
            'xInvoice' => $order->getOrderIncrementId(),
            'xCurrency' => $order->getCurrencyCode(),
            'xCardNum' => $payment->getAdditionalInformation("xCardNum"),
            // always true; order number is incremented on every attempt so invoice is always different
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
