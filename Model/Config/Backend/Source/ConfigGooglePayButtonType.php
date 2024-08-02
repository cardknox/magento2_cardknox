<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigGooglePayButtonType implements \Magento\Framework\Data\OptionSourceInterface
{
    public const GPAY_BUTTON_TYPE_PAY = 'pay';
    public const GPAY_BUTTON_TYPE_BUY = 'buy';
    public const GPAY_BUTTON_TYPE_PLAIN = 'plain';
    public const GPAY_BUTTON_TYPE_ORDER = 'order';
    public const GPAY_BUTTON_TYPE_CONTINUE = 'continue';
    public const GPAY_BUTTON_TYPE_SHORT = 'short';

    /**
     * Possible Google Pay button types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::GPAY_BUTTON_TYPE_PAY,
                'label' => __('pay')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_BUY,
                'label' => __('buy')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_PLAIN,
                'label' => __('plain')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_SHORT,
                'label' => __('short')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_ORDER,
                'label' => __('order')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_CONTINUE,
                'label' => __('continue')
            ],
        ];
    }
}
