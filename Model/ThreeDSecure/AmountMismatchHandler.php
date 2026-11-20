<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Model\ThreeDSecure;

use Psr\Log\LoggerInterface;

/**
 * Voids the authorization and logs the details when a 3DS authorized amount does not match the cart.
 */
class AmountMismatchHandler
{
    private const LOG_PREFIX = 'Sola 3DS amount mismatch';

    /**
     * @param AuthorizationVoider $authorizationVoider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AuthorizationVoider $authorizationVoider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Void the authorization and log the mismatch; never throws.
     *
     * @param array $response
     * @param string $cartAmount
     * @param int $quoteId
     * @param int $storeId
     * @return void
     */
    public function handle(array $response, string $cartAmount, int $quoteId, int $storeId): void
    {
        $refNum = $this->toScalarString($response['xRefNum'] ?? '');
        $voided = $this->authorizationVoider->void($refNum, $storeId);

        $context = [
            'ref_num' => $refNum,
            'approved_amount' => $this->toScalarString($response['xAuthAmount'] ?? ''),
            'cart_amount' => $cartAmount,
            'quote_id' => $quoteId,
            'store_id' => $storeId,
            'void_succeeded' => $voided,
        ];

        if ($voided) {
            $this->logger->warning(self::LOG_PREFIX . ': authorization was voided.', $context);

            return;
        }

        $this->logger->critical(
            self::LOG_PREFIX . ': void failed, the authorization must be voided manually in the Sola portal.',
            $context
        );
    }

    /**
     * Cast scalar values to string; anything else becomes an empty string.
     *
     * @param mixed $value
     * @return string
     */
    private function toScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
