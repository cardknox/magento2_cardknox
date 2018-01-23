<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Block;


use Magento\Backend\Model\Session\Quote;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config as GatewayConfig;
use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
/**
 * Class Form
 */
class Form extends Cc
{


    protected $gatewayConfig;


    /**
     * @param Context $context
     * @param Config $paymentConfig
     * @param GatewayConfig $gatewayConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        GatewayConfig $gatewayConfig,

        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->gatewayConfig = $gatewayConfig;
    }


}
