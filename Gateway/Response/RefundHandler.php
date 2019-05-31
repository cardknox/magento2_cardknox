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

    const REFNUM = 'xRefNum';

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
        $payment->setTransactionId($response['xRefNum']);
        $log['setTransactionId'] = $response['xRefNum'];
        $payment->setIsTransactionClosed(true);
        $log['setIsTransactionClosed'] = true;
        $payment->setShouldCloseParentTransaction(true);
        $log['setShouldCloseParentTransaction'] = true;
        $this->logger->debug($log);
        if (isset($response['xError']) && $response['xError'] != "" ) {
            $comment = $payment->getOrder()->addStatusHistoryComment($response['xError']);
            $payment->getOrder()->addRelatedObject($comment);
        }
    }
}
