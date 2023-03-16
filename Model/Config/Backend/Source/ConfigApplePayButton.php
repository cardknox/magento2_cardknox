<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigApplePayButton implements \Magento\Framework\Data\OptionSourceInterface
{
    public const BUTTON_BLACK = 'black';
    public const BUTTON_WHITE = 'white';
    public const BUTTON_WHITEOUTLINE = 'whiteOutline';

    /**
     * Possible Apple Pay button styles
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
            [
                'value' => self::BUTTON_WHITEOUTLINE,
                'label' => __('WhiteOutline')
            ],
        ];
    }
}
