<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
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
     * @return void
     */
    public function getTokenKey()
    {
        return $this->getValue(self::CARDKNOX_TOKEN_KEY);
    }

    /**
     * GetTokenKey function
     *
     * @return void
     */
    public function getTransactionKey()
    {
        return $this->getValue(self::CARDKNOX_TRANSACTION_KEY);
    }

    /**
     * GetGatewayUrl function
     *
     * @return void
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
//        $result = $this->getValue(self::KEY_CC_TYPES_CARDKNOX_MAPPER);
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
     * @return void
     */
    public function getGoogleRepCaptchaSiteKey()
    {
        return $this->getValue(self::GOOGLE_REPCAPTCHA_SITE_KEY);
    }
}
