<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public const IS_CC_SPLIT_CAPTURE_ENABLED = 'payment/cardknox/split_capture_enabled';
    public const IS_GPAY_SPLIT_CAPTURE_ENABLED = 'payment/cardknox_google_pay/split_capture_enabled';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
    
    /**
     * Format price to 0.00 format
     *
     * @param mixed $price
     * @return string
     * @since 100.1.0
     */
    public function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }

    /**
     * Check split capture enabled for cc
     *
     * @return string|null
     */
    public function isCCSplitCaptureEnabled()
    {
        return $this->scopeConfig->getValue(
            self::IS_CC_SPLIT_CAPTURE_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Check split capture enabled for GooglePay
     *
     * @return string|null
     */
    public function isGPaySplitCaptureEnabled()
    {
        return $this->scopeConfig->getValue(
            self::IS_GPAY_SPLIT_CAPTURE_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
