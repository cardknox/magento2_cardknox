<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class Data extends AbstractHelper
{
    public const IS_CC_SPLIT_CAPTURE_ENABLED = 'payment/cardknox/split_capture_enabled';
    public const IS_GPAY_SPLIT_CAPTURE_ENABLED = 'payment/cardknox_google_pay/split_capture_enabled';
    public const IS_CARDKNOX_GIFTCARD_ENABLED = 'payment/cardknox/ck_giftcard_enabled';
    public const IS_CARDKNOX_GIFTCARD_TEXT =  'payment/cardknox/ck_giftcard_text';

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param RemoteAddress $remoteAddress
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        RemoteAddress $remoteAddress
    ) {
        $this->remoteAddress = $remoteAddress;
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
    /**
     * Retrieves the visitor's IP address using the `RemoteAddress` instance. It will return IPv4
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        $ipAddress = null;
        $ipAddress = $this->remoteAddress->getRemoteAddress(false);
        return $ipAddress;
    }
    /**
     * Check split capture enabled for GooglePay
     *
     * @return string|null
     */
    public function isCardknoxGiftcardEnabled()
    {
        return $this->scopeConfig->getValue(
            self::IS_CARDKNOX_GIFTCARD_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Cardknox Gift card text
     *
     * @return string|null
     */
    public function cardknoxGiftcardText()
    {
        return $this->scopeConfig->getValue(
            self::IS_CARDKNOX_GIFTCARD_TEXT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
    /**
     * Get system config value function
     *
     * @param string $key
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $key,
            $storeId
        );
    }
}
