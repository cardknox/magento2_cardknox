<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ApplePayConfig
{
    public const DEFAULT_PATH_PATTERN = 'payment/%s/%s';
    public const KEY_ACTIVE = 'payment/cardknox_apple_pay/active';
    public const KEY_TITLE = 'payment/cardknox_apple_pay/title';
    public const MERCHANT_IDENTIFIER = 'payment/cardknox_apple_pay/merchant_identifier';
    public const ENVIRONMENT = 'payment/cardknox_apple_pay/environment';
    public const BUTTON_STYLE = 'payment/cardknox_apple_pay/ap_button_style';
    public const BUTTON_TYPE = 'payment/cardknox_apple_pay/ap_button_type';
    public const SPECIFICCOUNTRY = 'payment/cardknox_apple_pay/specificcountry';
    public const KEY_CC_TYPES = ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'];
    public const METHOD_CODE = 'cardknox_apple_pay';
    public const CARDKNOX_TOKEN_KEY = 'cardknox_token_key';
    public const KEY_CC_TYPES_CARDKNOX_MAPPER = 'cctypes_cardknox_mapper';
    public const APPLEPAY_PAYMENT_ACTION = 'payment/cardknox_apple_pay/payment_action';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->pathPattern = $pathPattern;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * IsActive function
     *
     * @return boolean
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get Apple Pay Title
     *
     * @return string
     */
    public function getApplePayTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    /**
     * GetMerchantIdentifier function
     *
     * @return string
     */
    public function getMerchantIdentifier()
    {
        return $this->getValue(self::MERCHANT_IDENTIFIER);
    }

    /**
     * GetEnvironment function
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getValue(self::ENVIRONMENT);
    }

    /**
     * GetButtonStyle function
     *
     * @return string
     */
    public function getButtonStyle()
    {
        return $this->getValue(self::BUTTON_STYLE);
    }

    /**
     * GetButtonType function
     *
     * @return string
     */
    public function getApplePayButtonType()
    {
        return $this->getValue(self::BUTTON_TYPE);
    }

    /**
     * Get GooglePay payment action function
     *
     * @return string
     */
    public function getGPayPaymentAction()
    {
        return $this->getValue(self::APPLEPAY_PAYMENT_ACTION);
    }
}
