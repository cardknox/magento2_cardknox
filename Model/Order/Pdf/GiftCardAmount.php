<?php
namespace CardknoxDevelopment\Cardknox\Model\Order\Pdf;

use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;

class GiftCardAmount extends DefaultTotal
{
    /**
     * Get Totals For Display
     *
     * @return array<mixed|string>[]
     */
    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        // Ensure the gift card amount is negative
        $amount = $this->getOrder()->getBaseCurrency()->formatTxt(-abs($this->getAmount()));

        $totals = [
            [
                'amount' => $this->getAmountPrefix() . $amount,
                'label' => __($this->getTitle()) . ':',
                'font_size' => $fontSize,
            ],
        ];

        return $totals;
    }
}
