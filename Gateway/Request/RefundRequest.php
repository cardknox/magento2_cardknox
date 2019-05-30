<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Logger;

class RefundRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

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

        $order = $paymentDO->getOrder();

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $command = "cc:voidrefund";
        $amount = $this->formatPrice($buildSubject['amount']);
        if ($amount != $order->getGrandTotalAmount()) {
            $command = "cc:refund";
        }
        $log['GrandTotalAmount'] = $order->getGrandTotalAmount();
        $log['command'] = $command;
        $this->logger->debug($log);

        return [
            'xCommand' => $command,
            'xAmount'   => $amount,
            'xRefNum' => $payment->getParentTransactionId(),
        ];
    }
}
