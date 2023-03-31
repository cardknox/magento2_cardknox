<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadataInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;

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
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        Config $config
    ) {
        $this->productMetadata = $productMetadata;
        $this->config = $config;
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
        
        return [
            'xVersion' => '4.5.8',
            'xSoftwareName' => $xSoftwareName,
            'xSoftwareVersion' => '1.0.15',
            'xKey' => $this->config->getValue(
                'cardknox_transaction_key',
                $order->getStoreId()
            ),
            'xDigitalWalletType' => 'GooglePay',
            'xIP' => $order->getRemoteIp(),
        ];
    }
}
