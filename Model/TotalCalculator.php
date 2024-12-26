<?php
namespace CardknoxDevelopment\Cardknox\Model;

class TotalCalculator
{
    /**
     * Base amount of the gift card used
     *
     * @var float
     */
    protected $baseAmount = 0;

    /**
     * Remaining grand total after all deductions
     *
     * @var float
     */
    protected $quoteGrandTotalLeft = 0;

    /**
     * Apply the gift card balance to the quote total.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param float $giftCardBalance
     * @return $this
     */
    public function collectQuoteCKGiftCard($quote, $total, $giftCardBalance)
    {
        if ($giftCardBalance > 0) {
            // Initialize base amount used
            $this->baseAmount = 0;

            // Calculate the remaining grand total after the discount
            $this->quoteGrandTotalLeft = $this->calculateGrandTotal($total);

            // Apply the gift card balance if grand total is positive
            if ($this->quoteGrandTotalLeft > 0) {
                $this->applyGiftCard($quote, $giftCardBalance);
                $quote->save(); // Save the updated quote
            }
        }
        return $this;
    }

    /**
     * Calculate the remaining grand total after subtotal, shipping, tax, and discount.
     *
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return float
     */
    protected function calculateGrandTotal($total)
    {
        $subtotal = $total->getBaseTotalAmount('subtotal');
        $shipping = $total->getBaseTotalAmount('shipping');
        $tax = $total->getBaseTotalAmount('tax');
        $discount = abs($total->getBaseTotalAmount('discount'));  // Use absolute value for discount
        return $subtotal + $shipping + $tax - $discount;
    }

    /**
     * Apply the gift card balance to the quote.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param float $giftCardBalance
     * @return void
     */
    protected function applyGiftCard($quote, $giftCardBalance)
    {
        if ($giftCardBalance < $this->quoteGrandTotalLeft) {
            $this->baseAmount = $giftCardBalance;
            $this->quoteGrandTotalLeft -= $giftCardBalance;
        } else {
            $this->baseAmount = $this->quoteGrandTotalLeft;
            $this->quoteGrandTotalLeft = 0;
        }

        // Ensure the new grand total is non-negative
        $quote->setData('base_grand_total', max(0, $quote->getData('base_grand_total') - $this->baseAmount));
        $quote->setData('grand_total', max(0, $quote->getData('grand_total') - $this->baseAmount));
    }

    /**
     * Get the base amount of the gift card used.
     *
     * @return float
     */
    public function getBaseAmount()
    {
        return $this->baseAmount;
    }
}
