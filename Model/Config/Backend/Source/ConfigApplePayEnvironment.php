<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigApplePayEnvironment implements \Magento\Framework\Data\OptionSourceInterface
{

    public const ENVIRONMENT_TEST = 'TEST';
    public const ENVIRONMENT_PRODUCTION = 'PRODUCTION';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENVIRONMENT_TEST,
                'label' => __('Test')
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
