<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

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

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverInterface = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider(
            $this->config,
            $this->resolverInterface
        );
    }

    public function testGetConfig()
    {
        $this->assertEquals(
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
                        'selectRecaptchaSource' => $this->config->getSelectReCaptchaSource(),
                        'isEnabledCardknoxGiftcard' => $this->config->isCardknoxGiftcardEnabled(),
                        'cardknoxGiftcardText' => $this->config->cardknoxGiftcardText(),
                        'isEnabledThreeDSEnabled' => $this->config->isEnable3DSecure(),
                        'ThreeDSEnvironment' => $this->config->get3DSecureEnvironment()
                    ],
                ],
            ],
            $this->configProvider->getConfig()
        );
    }
}
