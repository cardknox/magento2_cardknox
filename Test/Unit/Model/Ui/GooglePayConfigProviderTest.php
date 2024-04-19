<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Model\Ui\GooglePayConfigProvider;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\GpayConfig;
use Magento\Framework\Locale\ResolverInterface;

class GooglePayConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public const CODE = 'cardknox_google_pay';

    /**
     * Config variable
     *
     * @var Config
     */
    private $config;

    /**
     * GpayConfig variable
     *
     * @var GpayConfig
     */
    private $gpayConfig;

    /**
     * @var ResolverInterface
     */
    private $resolverInterface;

    /**
     * @var GooglePayConfigProvider
     */
    private $gPayConfigProvider;

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gpayConfig = $this->getMockBuilder(GpayConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverInterface = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gPayConfigProvider = new GooglePayConfigProvider($this->config, $this->gpayConfig, $this->resolverInterface);
    }

    public function testGetConfig()
    {
        static::assertEquals(
            [
                'payment' => [
                    GooglePayConfigProvider::CODE => [
                        'isActive' => $this->gpayConfig->isActive(),
                        'merchantName' => $this->gpayConfig->getMerchantName(),
                        'tokenKey' => $this->config->getTokenKey(),
                        'button'  => $this->gpayConfig->getButtonStyle(),
                        'GPEnvironment' => $this->gpayConfig->getEnvironment(),
                        'isGPaySplitCaptureEnabled' => $this->gpayConfig->isGPaySplitCaptureEnabled(),
                        'xPaymentAction' => $this->gpayConfig->getGPayPaymentAction(),
                        'buttonType' => $this->gpayConfig->getGooglePayButtonType()
                    ],
                ],
            ],
            $this->gPayConfigProvider->getConfig()
        );
    }
}
