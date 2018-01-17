<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cardknox';
    const CC_VAULT_CODE = 'cardknox_cc_vault';
    private $config;

    public function __construct(
        Config $config,
        ResolverInterface $localeResolver
    ) {
    
        $this->config = $config;
    }


    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'tokenKey' => $this->config->GetTokenKey(),
                    'ccVaultCode' => self::CC_VAULT_CODE
                ]
            ]
        ];
    }
}
