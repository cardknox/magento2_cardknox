<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadataInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Helper\Data;

class ApplePayBaseRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     * @param Data $helper
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        Config $config,
        Data $helper
    ) {
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];

        $order = $paymentDO->getOrder();
        $xSoftwareName = 'Magento ' . $this->productMetadata->getEdition() . " ". $this->productMetadata->getVersion();
        $ipAddress = $this->helper->getIpAddress();
        return [
            'xVersion' => '4.5.8',
            'xSoftwareName' => $xSoftwareName,
            'xSoftwareVersion' => '1.2.71',
            'xKey' => $this->config->getValue(
                'cardknox_transaction_key',
                $order->getStoreId()
            ),
            'xDigitalWalletType' => 'applepay',
            'xIP' => $ipAddress ? $ipAddress : $order->getRemoteIp(),
        ];
    }
}
