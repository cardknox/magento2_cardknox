<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_ACTIVE = 'active';
    public const KEY_CC_TYPES = ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'];
    public const METHOD_CODE = 'cardknox';
    public const CARDKNOX_TOKEN_KEY = 'cardknox_token_key';
    public const CARDKNOX_TRANSACTION_KEY = "cardknox_transaction_key";
    public const GATEWAYURL = 'cgi_url';
    public const KEY_CC_TYPES_CARDKNOX_MAPPER = 'cctypes_cardknox_mapper';
    public const IS_ENABLE_GOOGLE_REPCAPTCHA = "recaptchaEnabled";
    public const GOOGLE_REPCAPTCHA_SITE_KEY = "visible_api_key";
    public const CC_PAYMENT_ACTION = "payment_action";
    public const CC_SPLIT_CAPTURE_ENABLED = "split_capture_enabled";
    public const CC_SELECT_RECAPTCHA_SOURCE = "select_recaptcha_source";
    public const CARDKNOX_ENABLE_THREE_D_SECURE = "cardknox_enable_three_d_secure";
    public const CARDKNOX_THREE_D_SECURE_ENVIRONMENT = "cardknox_three_d_secure_environment";

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
     * GetTokenKey function
     *
     * @return string
     */
    public function getTokenKey()
    {
        return $this->getValue(self::CARDKNOX_TOKEN_KEY);
    }

    /**
     * GetTransactionKey function
     *
     * @return string
     */
    public function getTransactionKey()
    {
        return $this->getValue(self::CARDKNOX_TRANSACTION_KEY);
    }

    /**
     * GetGatewayUrl function
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        return $this->getValue(self::GATEWAYURL);
    }

    /**
     * Retrieve mapper between Magento and Braintree card types
     *
     * @return array
     */
    public function getCcTypesMapper()
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_CARDKNOX_MAPPER),
            true
        );
        return is_array($result) ? $result : [];
    }

    /**
     * IsEnabledReCaptcha function
     *
     * @return boolean
     */
    public function isEnabledReCaptcha()
    {
        return $this->getValue(self::IS_ENABLE_GOOGLE_REPCAPTCHA);
    }

    /**
     * GetGoogleRepCaptchaSiteKey function
     *
     * @return string
     */
    public function getGoogleRepCaptchaSiteKey()
    {
        return $this->getValue(self::GOOGLE_REPCAPTCHA_SITE_KEY);
    }

    /**
     * Get CC payment action function
     *
     * @return string
     */
    public function getCCPaymentAction()
    {
        return $this->getValue(self::CC_PAYMENT_ACTION);
    }

    /**
     * Enable split capture for autorize payment action function
     *
     * @return boolean
     */
    public function isCCSplitCaptureEnabled()
    {
        return (bool) $this->getValue(self::CC_SPLIT_CAPTURE_ENABLED);
    }

    /**
     * Select recaptcha source function
     *
     * Either google.com or recaptcha.net
     *
     * @return string
     */
    public function getSelectReCaptchaSource()
    {
        return $this->getValue(self::CC_SELECT_RECAPTCHA_SOURCE);
    }

    /**
     * IsActive 3D secure function
     *
     * @return boolean
     */
    public function isEnable3DSecure()
    {
        return (bool) $this->getValue(self::CARDKNOX_ENABLE_THREE_D_SECURE);
    }

    /**
     * Get 3D secure environment function
     *
     * @return string
     */
    public function get3DSecureEnvironment()
    {
        return $this->getValue(self::CARDKNOX_THREE_D_SECURE_ENVIRONMENT);
    }
}
