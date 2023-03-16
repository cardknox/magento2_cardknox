<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\ApplePayConfig;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

class ApplePayConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'cardknox_apple_pay';

    /**
     * Config variable
     *
     * @var Config
     */
    private $config;

    /**
     * GpayConfig variable
     *
     * @var ApplePayConfig
     */
    private $applePayConfig;

    /**
     * ApplePayConfigProvider function
     *
     * @param Config $config
     * @param ApplePayConfig $applePayConfig
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ApplePayConfig $applePayConfig,
        ResolverInterface $localeResolver
    ) {

        $this->applePayConfig = $applePayConfig;
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
                    'isActive' => $this->applePayConfig->isActive(),
                    'title' => $this->applePayConfig->getApplePayTitle(),
                    'merchantIdentifier' => $this->applePayConfig->getMerchantIdentifier(),
                    'tokenKey' => $this->config->getTokenKey(),
                    'xKey' => $this->config->getTransactionKey(),
                    'button'  => $this->applePayConfig->getButtonStyle(),
                    'type'  => $this->applePayConfig->getApplePayButtonType(),
                    'APEnvironment' => $this->applePayConfig->getEnvironment(),
                    'xPaymentAction' => $this->applePayConfig->getGPayPaymentAction()
                ]
            ]
        ];
    }
}
