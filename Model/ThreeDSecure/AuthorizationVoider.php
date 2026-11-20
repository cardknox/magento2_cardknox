<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Model\ThreeDSecure;

use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Gateway\Http\Client\Client;
use CardknoxDevelopment\Cardknox\Gateway\Http\TransferFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Psr\Log\LoggerInterface;

/**
 * Voids a gateway transaction by reference number, without requiring a Magento order.
 */
class AuthorizationVoider
{
    private const API_VERSION = '5.0.0';
    private const SOFTWARE_VERSION = '1.2.92';
    private const RESULT_APPROVED = 'A';

    /**
     * @param TransferFactory $transferFactory
     * @param Client $client
     * @param Config $config
     * @param ProductMetadataInterface $productMetadata
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TransferFactory $transferFactory,
        private readonly Client $client,
        private readonly Config $config,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Void the transaction; returns true only when the gateway approves the void.
     *
     * @param string $refNum
     * @param int $storeId
     * @return bool
     */
    public function void(string $refNum, int $storeId): bool
    {
        if ($refNum === '') {
            return false;
        }

        try {
            $response = $this->client->placeRequest(
                $this->transferFactory->create($this->buildRequest($refNum, $storeId))
            );
        } catch (\Throwable $e) {
            $this->logger->error('Cardknox authorization void failed: ' . $e->getMessage());

            return false;
        }

        return is_array($response) && ($response['xResult'] ?? null) === self::RESULT_APPROVED;
    }

    /**
     * Build the gateway void request.
     *
     * @param string $refNum
     * @param int $storeId
     * @return array
     */
    private function buildRequest(string $refNum, int $storeId): array
    {
        return [
            'xKey' => $this->config->getValue('cardknox_transaction_key', $storeId),
            'xVersion' => self::API_VERSION,
            'xSoftwareName' => 'Magento ' . $this->productMetadata->getEdition()
                . ' ' . $this->productMetadata->getVersion(),
            'xSoftwareVersion' => self::SOFTWARE_VERSION,
            'xSupports64BitRefnum' => true,
            'xCommand' => 'cc:void',
            'xRefNum' => $refNum,
        ];
    }
}
