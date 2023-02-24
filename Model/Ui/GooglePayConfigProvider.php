<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\GpayConfig;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

class GooglePayConfigProvider implements ConfigProviderInterface
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
     * GooglePayConfigProvider function
     *
     * @param Config $config
     * @param GpayConfig $gpayConfig
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        GpayConfig $gpayConfig,
        ResolverInterface $localeResolver
    ) {
    
        $this->gpayConfig = $gpayConfig;
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
                    'isActive' => $this->gpayConfig->isActive(),
                    'merchantName' => $this->gpayConfig->getMerchantName(),
                    'tokenKey' => $this->config->getTokenKey(),
                    'button'  => $this->gpayConfig->getButtonStyle(),
                    'GPEnvironment' => $this->gpayConfig->getEnvironment(),
                    'isGPaySplitCaptureEnabled' => $this->gpayConfig->isGPaySplitCaptureEnabled(),
                    'xPaymentAction' => $this->gpayConfig->getGPayPaymentAction()
                ]
            ]
        ];
    }
}
