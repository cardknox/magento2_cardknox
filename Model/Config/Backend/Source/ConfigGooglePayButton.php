<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigGooglePayButton implements \Magento\Framework\Data\OptionSourceInterface
{

    public const BUTTON_BLACK = 'black';
    public const BUTTON_WHITE = 'white';

    /**
     * Possible Google Pay button styles
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BUTTON_BLACK,
                'label' => __('Black')
            ],
            [
                'value' => self::BUTTON_WHITE,
                'label' => __('White')
            ],
        ];
    }
}
