<?php
namespace CardknoxDevelopment\Cardknox\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateOrderGiftCardInvoicedAmount implements ObserverInterface
{
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        if ($invoice->getCkgiftcardBaseAmount()) {
            $order->setBaseCkgiftCardsInvoiced(
                $order->getBaseCkgiftCardsInvoiced() + $invoice->getCkgiftcardBaseAmount()
            );
            $order->setCkgiftCardsInvoiced(
                $order->getCkgiftCardsInvoiced() + $invoice->getCkgiftcardAmount()
            );
        }
        return $this;
    }
}
