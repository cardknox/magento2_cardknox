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
use CardknoxDevelopment\Cardknox\Helper\Data;

class GooglePayCaptureRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Constructor
     *
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
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
        
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        $amount = $this->helper->formatPrice($payment->getAdditionalInformation("xAmount"));
        
        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        if ($payment->getLastTransId() == '') {
            return [
                'xCommand' => 'cc:sale',
                'xAmount'   => $amount,
                'xInvoice' => $order->getOrderIncrementId(),
                'xCurrency' => $order->getCurrencyCode(),
                'xCardNum' => $payment->getAdditionalInformation("xCardNum"),
                'xIgnoreInvoice' => true,
                'xTimeoutSeconds' => 55,
                'xAllowDuplicate' => true
            ];
        }

        return [
            'xCommand' => 'cc:capture',
            'xAmount'   => $amount,
            'xRefNum' => $payment->getLastTransId(),
            'xIgnoreInvoice' => true,
        ];
    }
}
