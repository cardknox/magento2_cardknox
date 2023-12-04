<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use CardknoxDevelopment\Cardknox\Helper\Data;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Data $helper
     * @param Config $config
     */
    public function __construct(
        Data $helper,
        Config $config
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

        $paymentDO = $buildSubject['payment'];
        $amount = $this->helper->formatPrice($buildSubject['amount']);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $cc_exp_month = $payment->getAdditionalInformation("cc_exp_month");
        $cc_exp_year = $payment->getAdditionalInformation("cc_exp_year");
        // phpcs:disable
        $ccPaymentAction = $payment->getAdditionalInformation("xPaymentAction") ? $payment->getAdditionalInformation("xPaymentAction") : $this->config->getCCPaymentAction();
        $isCCSplitCaptureEnabled = $payment->getAdditionalInformation("isSplitCapture") ? $payment->getAdditionalInformation("isSplitCapture") : $this->helper->isCCSplitCaptureEnabled();
        $xRequireSplitCapturable= 0;
        if ($isCCSplitCaptureEnabled == 1 && $ccPaymentAction == 'authorize') {
            $xRequireSplitCapturable = 1;
        }
        $isAllowDuplicateTransaction = $payment->getAdditionalInformation("isAllowDuplicateTransaction");
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
            'xRequireSplitCapturable' => $xRequireSplitCapturable,
            'xAllowDuplicate' => $isAllowDuplicateTransaction
        ];
    }
}
