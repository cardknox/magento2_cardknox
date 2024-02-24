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

class BaseRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @param ConfigInterface $config
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ConfigInterface $config,
        ProductMetadataInterface $productMetadata
    ) {
        $this->config = $config;
        $this->productMetadata = $productMetadata;
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
        $edition = $this->productMetadata->getEdition();
        $version = $this->productMetadata->getVersion();
        return [
            'xVersion' => '4.5.8',
            'xSoftwareName' => 'Magento ' . $edition . " ". $version,
            'xSoftwareVersion' => '1.0.21',
            'xKey' => $this->config->getValue(
                'cardknox_transaction_key',
                $order->getStoreId()
            ),
            'xIP' => $order->getRemoteIp(),
            'xSupports64BitRefnum' => true,
        ];
    }
}
