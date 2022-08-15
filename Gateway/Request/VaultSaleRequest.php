<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Payment\Helper\Formatter;

class VaultSaleRequest implements BuilderInterface
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

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];

        $amount = $this->formatPrice($buildSubject['amount']);
        $order = $paymentDO->getOrder();

        $payment = $paymentDO->getPayment();

        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        if ($payment->getLastTransId() == '') {
            return [
                'xCommand' => 'cc:sale',
                'xAmount'   => $amount,
                'xToken' => $paymentToken->getGatewayToken(),
                'xInvoice' => $order->getOrderIncrementId(),
                'xCurrency' => $order->getCurrencyCode(),
                'xIgnoreInvoice' => true,
                'xTimeoutSeconds' => 55
            ];
        }

        return [
            'xCommand' => 'cc:capture',
            'xAmount'   => $amount,
            'xRefNum' => $payment->getLastTransId(),
            'xIgnoreInvoice' => true
        ];
    }
}
