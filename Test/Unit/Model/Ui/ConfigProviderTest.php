<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $resolverInterface;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverInterface = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assetRepository = $this->getMockBuilder(AssetRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider(
            $this->config,
            $this->resolverInterface,
            $this->assetRepository
        );
    }

    public function testGetConfig()
    {
        $recaptchaNetApiJsPath = "https://example.com/path/CardknoxDevelopment_Cardknox/js/recaptcha/api.js";
        $recaptchaEnJsPath = "https://example.com/path/CardknoxDevelopment_Cardknox/js/recaptcha/recaptcha__en.js";

        static::assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'isActive' => $this->config->isActive(),
                        'tokenKey' => $this->config->getTokenKey(),
                        'ccVaultCode' => 'cardknox_cc_vault',
                        'isEnabledReCaptcha' => null,
                        'googleReCaptchaSiteKey' => null,
                        'isCCSplitCaptureEnabled' => $this->config->isCCSplitCaptureEnabled(),
                        'xPaymentAction' => $this->config->getCCPaymentAction(),
                        'recaptchaNetApiJsPath' => '',
                        'recaptchaEnJsPath' => '',
                        'selectRecaptchaSource' => $this->config->getSelectReCaptchaSource()
                    ],
                ],
            ],
            $this->configProvider->getConfig()
        );
    }

    /**
     * Get recaptcha.net api.js path function
     *
     * @return string
     */
    public function getRecaptchaNetApiJsPath()
    {
        return $this->assetRepository->createAsset('CardknoxDevelopment_Cardknox::js/recaptcha/api.js')->getUrl();
    }

    /**
     * Get recaptcha.net recaptcha__en.js path function
     *
     * @return string
     */
    public function getRecaptchaEnJsPath()
    {
        return $this->assetRepository->createAsset(
            'CardknoxDevelopment_Cardknox::js/recaptcha/recaptcha__en.js'
        )->getUrl();
    }
}
