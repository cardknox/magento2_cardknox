<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Config;

/**
 * Class Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_CC_TYPES = ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'];
    const METHOD_CODE = 'cardknox';
    const CARDKNOX_TOKEN_KEY = 'cardknox_token_key';
    const GATEWAYURL = 'cgi_url';
    const KEY_CC_TYPES_CARDKNOX_MAPPER = 'cctypes_cardknox_mapper';
    const IS_ENABLE_GOOGLE_REPCAPTCHA = "recaptchaEnabled";
    const GOOGLE_REPCAPTCHA_SITE_KEY = "visible_api_key";

    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    public function GetTokenKey()
    {
        return $this->getValue(self::CARDKNOX_TOKEN_KEY);
    }

    public function GetGatewayUrl()
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

    public function isEnabledReCaptcha()
    {
        return $this->getValue(self::IS_ENABLE_GOOGLE_REPCAPTCHA);
    }
    public function getGoogleRepCaptchaSiteKey()
    {
        return $this->getValue(self::GOOGLE_REPCAPTCHA_SITE_KEY);
    }
}