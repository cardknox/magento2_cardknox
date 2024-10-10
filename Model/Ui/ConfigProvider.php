<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'cardknox';
    public const CC_VAULT_CODE = 'cardknox_cc_vault';

    /**
     * Config variable
     *
     * @var Config
     */
    private $config;

    /**
     * ResolverInterface variable
     *
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * ConfigProvider function
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
    }

    /**
     * GetConfig function
     *
     * @return void
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'tokenKey' => $this->config->getTokenKey(),
                    'isEnabledReCaptcha' => $this->config->isEnabledReCaptcha(),
                    'googleReCaptchaSiteKey' => $this->config->getGoogleRepCaptchaSiteKey(),
                    'ccVaultCode' => self::CC_VAULT_CODE,
                    'isCCSplitCaptureEnabled' => $this->config->isCCSplitCaptureEnabled(),
                    'xPaymentAction' => $this->config->getCCPaymentAction(),
                    'selectRecaptchaSource' => $this->config->getSelectReCaptchaSource()
                ]
            ]
        ];
    }
}
