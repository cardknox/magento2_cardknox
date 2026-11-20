<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Model\ThreeDSecure;

use Magento\Quote\Model\Quote;

/**
 * Decides whether the amount approved after 3D Secure matches the current cart total.
 */
class AuthorizedAmountValidator
{
    private const APPROVED_AMOUNT_KEY = 'xAuthAmount';

    /**
     * Check that the approved amount is valid, positive and equals the cart base grand total.
     *
     * @param array $response
     * @param Quote $quote
     * @return bool
     */
    public function isMatch(array $response, Quote $quote): bool
    {
        $approved = $response[self::APPROVED_AMOUNT_KEY] ?? null;
        if (!is_string($approved) && !is_int($approved) && !is_float($approved)) {
            return false;
        }

        $approved = trim((string) $approved);
        if ($approved === '' || !is_numeric($approved) || (float) $approved <= 0) {
            return false;
        }

        return $this->format($approved) === $this->getCartAmount($quote);
    }

    /**
     * Get the cart base grand total formatted to two decimals.
     *
     * @param Quote $quote
     * @return string
     */
    public function getCartAmount(Quote $quote): string
    {
        return $this->format($quote->getData('base_grand_total'));
    }

    /**
     * Format a value to two decimals.
     *
     * @param mixed $value
     * @return string
     */
    private function format(mixed $value): string
    {
        return sprintf('%.2F', (float) $value);
    }
}
