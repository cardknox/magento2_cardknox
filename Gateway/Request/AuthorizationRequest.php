<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;

class AuthorizationRequest implements BuilderInterface
{
    use Formatter;
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

        /** @var PaymentDataObjectInterface $payment */

        $paymentDO = $buildSubject['payment'];
        $amount = $this->formatPrice($buildSubject['amount']);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        return [
            'xAmount' => $amount,
            'xExp' => sprintf('%02d%02d', $payment->getAdditionalInformation("cc_exp_month"), substr($payment->getAdditionalInformation("cc_exp_year"), -2)),
            'xCVV' => $payment->getAdditionalInformation("xCVV"),
            'xCommand' => 'cc:authonly',
            'xInvoice' => $order->getOrderIncrementId(),
            'xCurrency' => $order->getCurrencyCode(),
            'xCardNum' => $payment->getAdditionalInformation("xCardNum"),
            // always true; order number is incremented on every attempt so invoice is always different
            'xIgnoreInvoice' => true,
            'xTimeoutSeconds' => 55
        ];
    }
}
