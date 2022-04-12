<?php

namespace CardknoxDevelopment\Cardknox\Model\Methods;

class GooglePayMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    public const CODE = 'cardknox_google_pay';

    /**
     * @var string
     */
    public $_code = self::CODE;
}
