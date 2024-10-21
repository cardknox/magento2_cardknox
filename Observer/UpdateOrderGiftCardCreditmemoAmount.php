<?php
namespace CardknoxDevelopment\Cardknox\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for refund with gift card account
 */
class UpdateOrderGiftCardCreditmemoAmount implements ObserverInterface
{
    /**
     * Summary of execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        if ($order->getBaseCkgiftCardsRefunded() == $order->getCkgiftcardBaseAmount()) {
            return $this;
        }

        if ($creditmemo->getCkgiftcardBaseAmount()) {
            $order->setBaseCkgiftCardsRefunded(
                $order->getBaseCkgiftCardsRefunded() + $creditmemo->getCkgiftcardBaseAmount()
            );
            $order->setCkgiftCardsRefunded($order->getCkgiftCardsRefunded() + $creditmemo->getCkgiftcardAmount());
        }

        return $this;
    }
}
