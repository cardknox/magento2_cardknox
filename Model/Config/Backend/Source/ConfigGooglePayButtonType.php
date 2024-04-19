<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ConfigGooglePayButtonType implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Possible Google Pay button types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'pay',
                'label' => __('Pay')
            ],
            [
                'value' => 'buy',
                'label' => __('Buy')
            ],
            [
                'value' => 'plain',
                'label' => __('Plain')
            ],
            [
                'value' => 'order',
                'label' => __('Order')
            ],
            [
                'value' => 'donate',
                'label' => __('Donate')
            ],
            [
                'value' => '',
                'label' => __('Continue')
            ],
            [
                'value' => 'checkout',
                'label' => __('Checkout')
            ],
            [
                'value' => 'subscribe',
                'label' => __('Subscribe')
            ],
            [
                'value' => 'book',
                'label' => __('Book')
            ],
        ];
    }
}
