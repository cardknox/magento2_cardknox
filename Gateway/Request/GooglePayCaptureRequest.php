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
use CardknoxDevelopment\Cardknox\Gateway\Config\GpayConfig;

class GooglePayCaptureRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var Data
     */
    private $helper;

    /**
     *
     * @var GpayConfig
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Data $helper
     * @param GpayConfig $config
     */
    public function __construct(
        Data $helper,
        GpayConfig $config
    ) {
        $this->helper = $helper;
        $this->config = $config;
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
        $isAllowDuplicateTransactionGpCapture = $payment->getAdditionalInformation("isAllowDuplicateTransaction");
        if ($payment->getLastTransId() == '') {
            return [
                'xCommand' => 'cc:sale',
                'xAmount'   => $amount,
                'xInvoice' => $order->getOrderIncrementId(),
                'xCurrency' => $order->getCurrencyCode(),
                'xCardNum' => $payment->getAdditionalInformation("xCardNum"),
                'xIgnoreInvoice' => true,
                'xTimeoutSeconds' => 55,
                'xAllowDuplicate' => $isAllowDuplicateTransactionGpCapture
            ];
        }
        // phpcs:disable
        $gPayPaymentAction = $payment->getAdditionalInformation("xPaymentAction") ? $payment->getAdditionalInformation("xPaymentAction") : $this->config->getGPayPaymentAction();
        $isGPaySplitCaptureEnabled = $payment->getAdditionalInformation("isSplitCapture") ? $payment->getAdditionalInformation("isSplitCapture") : $this->helper->isGPaySplitCaptureEnabled();
        $xCommand = 'cc:capture';
        $transactionId = null;
        $transactionId = $payment->getLastTransId();
        if ($isGPaySplitCaptureEnabled == 1 && $gPayPaymentAction == 'authorize') {
            $xCommand = 'cc:splitcapture';
            $transactionId = $payment->getParentTransactionId();
            $amount = $this->helper->formatPrice($buildSubject['amount']) ? $this->helper->formatPrice($buildSubject['amount']) : $this->helper->formatPrice($payment->getAdditionalInformation("xAmount"));
        }
        
        return [
            'xCommand' => $xCommand,
            'xAmount'   => $amount,
            'xRefNum' => $transactionId,
            'xIgnoreInvoice' => true,
        ];
    }
}
