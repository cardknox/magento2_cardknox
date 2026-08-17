<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Logger;
use CardknoxDevelopment\Cardknox\Helper\Data;

class RefundRequest implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        Data $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
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

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        // Read the total from the order itself rather than from $paymentDO->getOrder(): the
        // order adapter is a global preference that another module can replace with one
        // declaring a float return while still handing back the string the database gives,
        // which is a TypeError under strict types.
        $grandTotal = $this->helper->formatPrice($payment->getOrder()->getBaseGrandTotal());

        $command = "cc:voidrefund";
        $amount = $this->helper->formatPrice($buildSubject['amount']);
        if ($amount != $grandTotal) {
            $command = "cc:refund";
        }
        $log['GrandTotalAmount'] = $grandTotal;
        $log['command'] = $command;
        $this->logger->debug($log);

        return [
            'xCommand' => $command,
            'xAmount'   => $amount,
            'xRefNum' => $payment->getParentTransactionId(),
        ];
    }
}
