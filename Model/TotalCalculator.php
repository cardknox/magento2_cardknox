<?php
namespace CardknoxDevelopment\Cardknox\Model;

class TotalCalculator
{
    /**
     * _baseAmount variable
     *
     * @var integer
     */
    protected $_baseAmount = 0;

    /**
     * _quoteGrandTotalLeft variable
     *
     * @var integer
     */
    protected $quoteGrandTotalLeft = 0;

    /**
     * _baseAmountUsed variable
     *
     * @var integer
     */
    protected $_baseAmountUsed = 0;

    public function collectQuoteCKGiftCard($quote, $total, $giftCardBalance)
    {
       
        $giftCardCollectedFlag = $quote->getGiftCardCollectedFlag();
        if (!$giftCardCollectedFlag) {
        
            // Initialization of amounts
            $this->_baseAmount = 0;
            $this->_baseAmountUsed = 0;
            // Calculate the quote grand total left after all other discounts and fees
            $subtotal = $total->getBaseTotalAmount('subtotal');
            $shipping = $total->getBaseTotalAmount('shipping');
            $tax = $total->getBaseTotalAmount('tax');
            $discount = abs($total->getBaseTotalAmount('discount'));  // Absolute value of discount

            // Calculate the remaining grand total after the discount
            $this->quoteGrandTotalLeft = $subtotal + $shipping + $tax - $discount;
            // Ensure the grand total is positive before applying gift card
            if ($this->quoteGrandTotalLeft > 0 && $giftCardBalance) {
                // Apply the gift card, ensuring the total does not go negative
                if ($giftCardBalance < $this->quoteGrandTotalLeft) {
                    $this->_baseAmount += $giftCardBalance;
                    $this->quoteGrandTotalLeft -= $giftCardBalance;
                } else {
                    // Cap the gift card amount to the remaining grand total
                    $this->_baseAmount += $this->quoteGrandTotalLeft;
                    $this->quoteGrandTotalLeft = 0;
                }
                // Set the new grand total ensuring it's not negative
                $quoteBaseGrandTotal = max(0, $quote->getData('base_grand_total') - $this->_baseAmount);
                $quoteGrandTotal = max(0, $quote->getData('grand_total') - $this->_baseAmount);
                // Apply the new totals
                $quote->setData('base_grand_total', $quoteBaseGrandTotal);
                $quote->setData('grand_total', $quoteGrandTotal);
                // Save the quote
                $quote->save();
            }
        }
        return $this;
    }

    public function getBaseAmount()
    {
        return $this->_baseAmount;
    }
}
