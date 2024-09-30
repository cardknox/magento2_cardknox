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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/aaaa.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('giftCardCollectedFlag'. $giftCardCollectedFlag);
        if (!$giftCardCollectedFlag) {
            $this->_baseAmount = 0;
            $this->_baseAmountUsed = 0;

            $subtotal = $total->getBaseTotalAmount('subtotal');
            $shipping =   $total->getBaseTotalAmount('shipping');
            $tax = $total->getBaseTotalAmount('tax');
            $weee =  $total->getBaseTotalAmount('weee');
            $wee_tax =  $total->getBaseTotalAmount('weee_tax');
            $discount =  $total->getBaseTotalAmount('discount');
            $discount_tax_compensation =$total->getBaseTotalAmount('discount_tax_compensation');
            $shipping_discount_tax_compensation =  $total->getBaseTotalAmount('shipping_discount_tax_compensation');
            $this->quoteGrandTotalLeft=  $subtotal
                +   $shipping
                +  $tax
                + $weee
                + $wee_tax

                - $discount
                - $discount_tax_compensation
                - $shipping_discount_tax_compensation

            ;

            if ($giftCardBalance < $this->quoteGrandTotalLeft) {
                $this->_baseAmount +=$giftCardBalance;
                $this->quoteGrandTotalLeft = $this->quoteGrandTotalLeft-$giftCardBalance;
            } elseif ($giftCardBalance ==$this->quoteGrandTotalLeft) {
                $this->_baseAmount  +=$giftCardBalance;
                $this->quoteGrandTotalLeft = 0;
            } elseif ($giftCardBalance > $this->quoteGrandTotalLeft) {
                $this->_baseAmount += $this->quoteGrandTotalLeft;
                $quoteGrandTotalLeft = 0;
            }
        }
        $quote->setGiftCardCollectedFlag(true);
    }

    public function getBaseAmount()
    {
        return $this->_baseAmount;
    }
}
