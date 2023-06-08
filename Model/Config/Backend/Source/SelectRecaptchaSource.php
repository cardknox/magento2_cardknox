<?php

namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class SelectRecaptchaSource implements \Magento\Framework\Data\OptionSourceInterface
{

    public const GOOGLE_COM = 'google.com';
    public const RECAPTCHA_NET = 'recaptcha.net';

    /**
     * Select recaptcha source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::GOOGLE_COM,
                'label' => __('google.com')
            ],
            [
                'value' => self::RECAPTCHA_NET,
                'label' => __('recaptcha.net')
            ]
        ];
    }
}
