<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use CardknoxDevelopment\Cardknox\Helper\Data;
use CardknoxDevelopment\Cardknox\Gateway\Config\GpayConfig;

class GooglePayAuthorizationRequest implements BuilderInterface
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

        /** @var PaymentDataObjectInterface $payment */

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $amount = $this->helper->formatPrice($buildSubject['amount']);
        // phpcs:disable
        $gPayPaymentAction = $payment->getAdditionalInformation("xPaymentAction") ? $payment->getAdditionalInformation("xPaymentAction") : $this->config->getGPayPaymentAction();
        $isGPaySplitCaptureEnabled = $payment->getAdditionalInformation("isSplitCapture") ? $payment->getAdditionalInformation("isSplitCapture") : $this->helper->isGPaySplitCaptureEnabled();
        // phpcs:enable
        $xRequireSplitCapturable = 0;
        if ($isGPaySplitCaptureEnabled == 1 && $gPayPaymentAction == 'authorize') {
            $xRequireSplitCapturable = 1;
        }
        $isAllowDuplicateTransactionGpAuth = $payment->getAdditionalInformation("isAllowDuplicateTransaction");
        $result = [
            'xAmount' => $amount,
            'xCommand' => 'cc:authonly',
            'xInvoice' => $order->getOrderIncrementId(),
            'xCurrency' => $order->getCurrencyCode(),
            'xCardNum' => $payment->getAdditionalInformation("xCardNum"),
            // always true; order number is incremented on every attempt so invoice is always different
            'xIgnoreInvoice' => true,
            'xTimeoutSeconds' => 55,
            'xRequireSplitCapturable' => $xRequireSplitCapturable,
        ];

        if ($isAllowDuplicateTransactionGpAuth) {
            $result['xAllowDuplicate'] = true;
        }

        return $result;
    }
}
