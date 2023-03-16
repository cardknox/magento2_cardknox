<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigApplePayButtonType implements \Magento\Framework\Data\OptionSourceInterface
{
    public const BUTTON_TYPE_BUY = 'buy';
    public const BUTTON_TYPE_PAY = 'pay';
    public const BUTTON_TYPE_PLAIN = 'plain';
    public const BUTTON_TYPE_ORDER = 'order';
    public const BUTTON_TYPE_DONATE = 'donate';
    public const BUTTON_TYPE_CONTINUE = 'continue';
    public const BUTTON_TYPE_CHECKOUT = 'checkout';

    /**
     * Possible Apply Pay button types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BUTTON_TYPE_BUY,
                'label' => __('Buy')
            ],
            [
                'value' => self::BUTTON_TYPE_PAY,
                'label' => __('Pay')
            ],
            [
                'value' => self::BUTTON_TYPE_PLAIN,
                'label' => __('Plain')
            ],
            [
                'value' => self::BUTTON_TYPE_ORDER,
                'label' => __('Order')
            ],
            [
                'value' => self::BUTTON_TYPE_DONATE,
                'label' => __('Donate')
            ],
            [
                'value' => self::BUTTON_TYPE_CONTINUE,
                'label' => __('Continue')
            ],
            [
                'value' => self::BUTTON_TYPE_CHECKOUT,
                'label' => __('Checkout')
            ],
        ];
    }
}
