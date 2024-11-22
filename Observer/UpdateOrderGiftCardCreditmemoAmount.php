<?php
namespace CardknoxDevelopment\Cardknox\Observer;

use Magento\Framework\Event\ObserverInterface;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Observer for refund with gift card account
 */
class UpdateOrderGiftCardCreditmemoAmount implements ObserverInterface
{
    /**
     * @var Giftcard
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param Giftcard $helper
     */
    public function __construct(
        Giftcard $helper
    ) {
        $this->helper = $helper;
    }

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
            $giftIssueAmount = $creditmemo->getCkgiftcardAmount();

            $order->setCkgiftCardsRefunded($order->getCkgiftCardsRefunded() + $creditmemo->getCkgiftcardAmount());

            if ($giftIssueAmount > 0) {
                // gift:issue
                $this->helper->giftIssue($giftIssueAmount, $order);
            }
        }

        return $this;
    }
}
