<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

class CKGiftcardCreditmemo extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Undocumented function
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return void
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {

        $totalCkgiftcardAmount = 0;
        $baseTotalCkgiftcardAmoun = 0;
        $order = $creditmemo->getOrder();
        $totalCkgiftcardAmount = $order->getCkgiftcardAmount();
        $baseTotalCkgiftcardAmoun = $order->getCkgiftcardBaseAmount();

        $creditmemo->setCkgiftcardAmount(-$totalCkgiftcardAmount);
        $creditmemo->setCkgiftcardBaseAmount(-$baseTotalCkgiftcardAmoun);

        $grandTotal = abs($creditmemo->getGrandTotal() - $totalCkgiftcardAmount) < 0.0001
            ? 0 : $creditmemo->getGrandTotal() - $totalCkgiftcardAmount;
        $baseGrandTotal = abs($creditmemo->getBaseGrandTotal() - $baseTotalCkgiftcardAmoun) < 0.0001
            ? 0 : $creditmemo->getBaseGrandTotal() - $baseTotalCkgiftcardAmoun;
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);
        return $this;
    }
}
