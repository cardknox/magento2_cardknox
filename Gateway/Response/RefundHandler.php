<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Logger;

class RefundHandler implements HandlerInterface
{

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

    public const REFNUM = 'xRefNum';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setIsTransactionClosed(true);
        $log['setIsTransactionClosed'] = true;
        $payment->setShouldCloseParentTransaction(!(bool)$payment->getCreditmemo()->getInvoice()->canRefund());
        $log['setShouldCloseParentTransaction'] = !(bool)$payment->getCreditmemo()->getInvoice()->canRefund();
        $this->logger->debug($log);
    }
}
