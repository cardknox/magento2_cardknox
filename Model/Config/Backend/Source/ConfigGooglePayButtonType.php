<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigGooglePayButtonType implements \Magento\Framework\Data\OptionSourceInterface
{
    public const GPAY_BUTTON_TYPE_PAY = 'pay';
    public const GPAY_BUTTON_TYPE_BUY = 'buy';
    public const GPAY_BUTTON_TYPE_PLAIN = 'plain';
    public const GPAY_BUTTON_TYPE_ORDER = 'order';
    public const GPAY_BUTTON_TYPE_DONATE = 'donate';
    public const GPAY_BUTTON_TYPE_CONTINUE = 'continue';
    public const GPAY_BUTTON_TYPE_CHECKOUT = 'checkout';
    public const GPAY_BUTTON_TYPE_SUBSCRIBE = 'subscribe';
    public const GPAY_BUTTON_TYPE_BOOK = 'book';

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
                'value' => self::GPAY_BUTTON_TYPE_ORDER,
                'label' => __('order')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_DONATE,
                'label' => __('donate')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_CONTINUE,
                'label' => __('continue')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_CHECKOUT,
                'label' => __('checkout')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_SUBSCRIBE,
                'label' => __('subscribe')
            ],
            [
                'value' => self::GPAY_BUTTON_TYPE_BOOK,
                'label' => __('book')
            ],
        ];
    }
}
