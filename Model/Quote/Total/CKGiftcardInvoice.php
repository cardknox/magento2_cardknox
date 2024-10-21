<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class CKGiftcardInvoice extends AbstractTotal
{
    /**
     * Collect function
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return void
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $baseGiftCardAmount = (float) $order->getCkgiftcardBaseAmount();
        $invoicedBaseGiftCardAmount = (float) $order->getBaseCkgiftCardsInvoiced();
        $remainingBaseGiftCardAmount = $baseGiftCardAmount - $invoicedBaseGiftCardAmount;

        $appliedGiftCardAmount = null;
        $appliedBaseGiftCardAmount = null;
        if ($baseGiftCardAmount && $invoicedBaseGiftCardAmount != $baseGiftCardAmount) {

            if ($remainingBaseGiftCardAmount >= $invoice->getBaseGrandTotal()) {
                // Use gift card to cover the entire invoice amount
                $appliedBaseGiftCardAmount = $invoice->getBaseGrandTotal();
                $appliedGiftCardAmount = $invoice->getGrandTotal();

                $invoice->setBaseGrandTotal(0)
                        ->setGrandTotal(0);
            } else {
                // Partial gift card usage
                $appliedBaseGiftCardAmount = $remainingBaseGiftCardAmount;

                $giftCardAmount = (float) $order->getCkgiftcardAmount();
                $invoicedGiftCardAmount = (float) $order->getCkgiftCardsInvoiced();
                $appliedGiftCardAmount = $giftCardAmount - $invoicedGiftCardAmount;

                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $appliedBaseGiftCardAmount)
                        ->setGrandTotal($invoice->getGrandTotal() - $appliedGiftCardAmount);
            }

        }
        $invoice->setCkgiftcardBaseAmount($appliedBaseGiftCardAmount)
                    ->setCkgiftcardAmount($appliedGiftCardAmount);

        return $this;
    }
}
