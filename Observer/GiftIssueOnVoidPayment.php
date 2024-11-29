<?php
namespace CardknoxDevelopment\Cardknox\Observer;

use CardknoxDevelopment\Cardknox\Helper\Giftcard as GiftCardHelper;

class GiftIssueOnVoidPayment implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var GiftCardHelper
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param GiftCardHelper $helper
     */
    public function __construct(
        GiftCardHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Gift issue on payment void action function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment(); // Get payment object
        $order = $payment->getOrder();
        $giftIssueAmount = $order->getCkgiftcardAmount();
        if ($giftIssueAmount > 0) {
            // gift:issue
            $this->helper->giftIssue($giftIssueAmount, $order);
        }
    }
}
