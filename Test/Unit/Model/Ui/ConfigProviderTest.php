<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{

    private $config;
    private $resolverInterface;
    private $configProvider;

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverInterface = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = new ConfigProvider($this->config, $this->resolverInterface);
    }

    public function testGetConfig()
    {


        static::assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'isActive' => $this->config->isActive(),
                        'tokenKey' => $this->config->getTokenKey(),
                        'ccVaultCode' => 'cardknox_cc_vault',
                        'isEnabledReCaptcha' => null,
                        'googleReCaptchaSiteKey' => null,
                    ],
                ],
            ],
            $this->configProvider->getConfig()
        );
    }
}
