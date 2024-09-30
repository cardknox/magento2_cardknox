<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

class CKGiftcard extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
      
        $ckGiftcardAmount = $quote->getCkgiftcardAmount();
        $total->setCkgiftcardAmount(-$ckGiftcardAmount);
        $total->setCkgiftcardBaseAmount(-$ckGiftcardAmount);
        $total->setTotalAmount('ckgiftcard_amount', -$ckGiftcardAmount);
        $total->setBaseTotalAmount('ckgiftcard_base_amount', -$ckGiftcardAmount);
        $quote->setCkgiftcardAmount($ckGiftcardAmount);
        $quote->setCkgiftcardBaseAmount($ckGiftcardAmount);
        
        return $this;
    }
    
    protected function clearValues(Address\Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }

    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $giftcardAmount = $quote->getCkgiftcardAmount();
        $result = [];
        $result =  [
            'code' => 'ckgiftcard',
            'title' => 'Giftcard',
            'value' => -$giftcardAmount
        ];
        return $result;
    }
}