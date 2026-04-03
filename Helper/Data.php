<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

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
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param RemoteAddress $remoteAddress
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        RemoteAddress $remoteAddress,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->isSingleSourceMode = $isSingleSourceMode;
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

    /**
     * Check if Magento MSI (Multi-Source Inventory) is actively used
     *
     * Uses IsSingleSourceModeInterface which queries the inventory_source table.
     * - 0 or 1 enabled source → single-source mode → returns false (MSI not active)
     * - 2+ enabled sources → multi-source mode → returns true (MSI active)
     *
     * @return bool
     */
    public function isMsiEnabled(): bool
    {
        return !$this->isSingleSourceMode->execute();
    }

    /**
     * Get shipping origin ZIP/Postal Code from store config
     *
     * @return string|null
     */
    public function getShippingOriginZip(): ?string
    {
        return $this->scopeConfig->getValue(
            'shipping/origin/postcode',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
