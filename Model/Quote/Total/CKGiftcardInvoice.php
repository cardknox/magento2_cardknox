<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

class CKGiftcardInvoice extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Undocumented function
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return void
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {

        $totalCkgiftcardAmount = 0;
        $baseTotalCkgiftcardAmoun = 0;
        $order = $invoice->getOrder();
        $totalCkgiftcardAmount = $order->getCkgiftcardAmount();
        $baseTotalCkgiftcardAmoun = $order->getCkgiftcardBaseAmount();

        $invoice->setCkgiftcardAmount(-$totalCkgiftcardAmount);
        $invoice->setCkgiftcardBaseAmount(-$baseTotalCkgiftcardAmoun);

        $grandTotal = abs($invoice->getGrandTotal() - $totalCkgiftcardAmount) < 0.0001
            ? 0 : $invoice->getGrandTotal() - $totalCkgiftcardAmount;
        $baseGrandTotal = abs($invoice->getBaseGrandTotal() - $baseTotalCkgiftcardAmoun) < 0.0001
            ? 0 : $invoice->getBaseGrandTotal() - $baseTotalCkgiftcardAmoun;
        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);
        return $this;
    }
}