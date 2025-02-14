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
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class GooglePayBaseRequest implements BuilderInterface
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
     * @var Helper
     */
    private $dataHelper;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     * @param Data $dataHelper
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        Config $config,
        Helper $dataHelper
    ) {
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        $this->dataHelper = $dataHelper;
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
        $_ipAddress = $this->dataHelper->getIpAddress();
        return [
            'xVersion' => '5.0.0',
            'xSoftwareName' => $xSoftwareName,
            'xSoftwareVersion' => '1.2.73',
            'xKey' => $this->config->getValue(
                'cardknox_transaction_key',
                $order->getStoreId()
            ),
            'xDigitalWalletType' => 'GooglePay',
            'xIP' => $_ipAddress ? $_ipAddress : $order->getRemoteIp(),
        ];
    }
}
