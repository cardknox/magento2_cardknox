<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigGooglePayButtonSizeMode implements \Magento\Framework\Data\OptionSourceInterface
{

    public const FILL = 'fill';
    public const STATIC = 'static';

    /**
     * Possible Google Pay button styles
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::FILL,
                'label' => __('fill')
            ],
            [
                'value' => self::STATIC,
                'label' => __('static')
            ],
        ];
    }
}
