<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

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
     * AssetRepository variable
     *
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * ConfigProvider function
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        AssetRepository $assetRepository
    ) {
    
        $this->config = $config;
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get recaptcha.net api.js path function
     *
     * @return string|null
     */
    public function getRecaptchaNetApiJsPath(): string
    {
        $recaptchaNetApiJsPath = $this->assetRepository->createAsset(
            'CardknoxDevelopment_Cardknox::js/recaptcha/api.js'
        );
        return isset($recaptchaNetApiJsPath) ? (string) $recaptchaNetApiJsPath->getUrl() : '';
    }

    /**
     * Get recaptcha.net recaptcha__en.js path function
     *
     * @return string|null
     */
    public function getRecaptchaEnJsPath(): string
    {
        $recaptchaEnJsPath = $this->assetRepository->createAsset(
            'CardknoxDevelopment_Cardknox::js/recaptcha/recaptcha__en.js'
        );
        return isset($recaptchaEnJsPath) ? (string) $recaptchaEnJsPath->getUrl() : '';
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
                    'recaptchaNetApiJsPath' => $this->getRecaptchaNetApiJsPath(),
                    'recaptchaEnJsPath' => $this->getRecaptchaEnJsPath(),
                    'selectRecaptchaSource' => $this->config->getSelectReCaptchaSource()
                ]
            ]
        ];
    }
}
