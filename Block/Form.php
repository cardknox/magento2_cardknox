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
use Magento\Vault\Model\VaultPaymentInterface;

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

    /**
     * Check if vault enabled
     * @return bool
     */
//    public function isVaultEnabled()
//    {
//
//        $storeId = $this->_storeManager->getStore()->getId();
//       $enabled = $this->gatewayConfig->getValue('cardknox_cc_vault_active', $storeId);
//
//        $vaultPayment = $this->getVaultPayment();
//        return $vaultPayment->isActive($storeId);
//    }

    /**
     * Get configured vault payment for Cardknox
     * @return VaultPaymentInterface
     */
//    private function getVaultPayment()
//    {
//        return $this->getPaymentDataHelper()->getMethodInstance(ConfigProvider::CC_VAULT_CODE);
//    }


    //TODO need to find a non deprecated way of doing the below
    /**
     * Get payment data helper instance
     * @deprecated
     * @return Data
     */
//    private function getPaymentDataHelper()
//    {
//        if ($this->paymentDataHelper === null) {
//            $this->paymentDataHelper = ObjectManager::getInstance()->get(Data::class);
//        }
//        return $this->paymentDataHelper;
//    }
}
