<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Gateway\Config\ApplePayConfig;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Model\Ui\ApplePayConfigProvider;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ApplePayConfigProviderTest extends TestCase
{
    /**
     * @var ApplePayConfigProvider
     */
    private $configProvider;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var ApplePayConfig|MockObject
     */
    private $applePayConfig;

    /**
     * @var ResolverInterface|MockObject
     */
    private $localeResolver;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->applePayConfig = $this->getMockBuilder(ApplePayConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)
            ->getMockForAbstractClass();
            
        $this->configProvider = $this->objectManager->getObject(
            ApplePayConfigProvider::class,
            [
                'config' => $this->config,
                'applePayConfig' => $this->applePayConfig,
                'localeResolver' => $this->localeResolver
            ]
        );
    }

    /**
     * Test getConfig method
     */
    public function testGetConfig()
    {
        $tokenKey = 'test-token-key';
        $isActive = true;
        $title = 'Apple Pay by Cardknox';
        $merchantIdentifier = 'merchant.com.test';
        $buttonStyle = 'black';
        $buttonType = 'buy';
        $paymentAction = 'authorize';
        $showSummary = true;

        $this->config->expects($this->once())
            ->method('getTokenKey')
            ->willReturn($tokenKey);

        $this->applePayConfig->expects($this->once())
            ->method('isActive')
            ->willReturn($isActive);

        $this->applePayConfig->expects($this->once())
            ->method('getApplePayTitle')
            ->willReturn($title);

        $this->applePayConfig->expects($this->once())
            ->method('getMerchantIdentifier')
            ->willReturn($merchantIdentifier);

        $this->applePayConfig->expects($this->once())
            ->method('getButtonStyle')
            ->willReturn($buttonStyle);

        $this->applePayConfig->expects($this->once())
            ->method('getApplePayButtonType')
            ->willReturn($buttonType);

        $this->applePayConfig->expects($this->once())
            ->method('getGPayPaymentAction')
            ->willReturn($paymentAction);

        $this->applePayConfig->expects($this->once())
            ->method('isEnabledApplePayShowSummary')
            ->willReturn($showSummary);

        $expectedResult = [
            'payment' => [
                ApplePayConfigProvider::CODE => [
                    'isActive' => $isActive,
                    'title' => $title,
                    'merchantIdentifier' => $merchantIdentifier,
                    'tokenKey' => $tokenKey,
                    'button' => $buttonStyle,
                    'type' => $buttonType,
                    'xPaymentAction' => $paymentAction,
                    'isEnabledApplePayShowSummary' => $showSummary
                ]
            ]
        ];

        $result = $this->configProvider->getConfig();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test ApplePayConfigProvider code constant
     */
    public function testConstantValue()
    {
        $this->assertEquals('cardknox_apple_pay', ApplePayConfigProvider::CODE);
    }
}