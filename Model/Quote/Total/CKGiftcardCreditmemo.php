<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class CKGiftcardCreditmemo extends AbstractTotal
{
    /**
     * Collect function call for credit memo
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return void
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $baseGiftCardInvoiced = $order->getBaseCkgiftCardsInvoiced();
        $baseGiftCardRefunded = $order->getBaseCkgiftCardsRefunded();
        $remainingBaseGiftCard = $baseGiftCardInvoiced - $baseGiftCardRefunded;

        if ($order->getCkgiftcardBaseAmount() && $baseGiftCardInvoiced != 0) {
            $appliedGiftCardAmount = 0;
            $appliedBaseGiftCardAmount = 0;

            if ($remainingBaseGiftCard >= $creditmemo->getBaseGrandTotal()) {
                // Use the gift card to cover the entire credit memo
                $appliedBaseGiftCardAmount = $creditmemo->getBaseGrandTotal();
                $appliedGiftCardAmount = $creditmemo->getGrandTotal();

                $creditmemo->setBaseGrandTotal(0)
                        ->setGrandTotal(0)
                        ->setAllowZeroGrandTotal(true);
            } else {
                // Partial application of the gift card
                $appliedBaseGiftCardAmount = $remainingBaseGiftCard;
                $appliedGiftCardAmount = $order->getCkgiftCardsInvoiced() - $order->getCkgiftCardsRefunded();

                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $appliedBaseGiftCardAmount)
                        ->setGrandTotal($creditmemo->getGrandTotal() - $appliedGiftCardAmount);
            }

            $creditmemo->setCkgiftcardBaseAmount($appliedBaseGiftCardAmount)
                    ->setCkgiftcardAmount($appliedGiftCardAmount);
        }

        return $this;
    }
}
