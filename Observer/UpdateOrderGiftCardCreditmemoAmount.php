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
     *
     * @var Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Constructor
     *
     * @param Giftcard $helper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Giftcard $helper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
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
            $giftIssueAmount = $order->getCkgiftCardsRefunded() + $creditmemo->getCkgiftcardAmount();

            $order->setCkgiftCardsRefunded($order->getCkgiftCardsRefunded() + $creditmemo->getCkgiftcardAmount());

            // gift:issue
            $this->giftIssue($giftIssueAmount, $order);
        }

        return $this;
    }

    /**
     * Gift:issue while credit memo generate function
     *
     * @param int|float|mixed $giftIssueAmount
     * @param mixed $order
     * @return void
     */
    protected function giftIssue($giftIssueAmount, $order)
    {
        $this->helper->giftAmountRefund($giftIssueAmount, $order);
        $ckGiftCardCode = $order->getCkgiftcardCode();
        $giftCardAmountWithCurrency = $this->helper->getFormattedAmount($giftIssueAmount);
        $ckGiftcardComment = 'The Cardknox gift card with code <b>'.$ckGiftCardCode.'</b> has been successfully issued for an amount of <b>'.$giftCardAmountWithCurrency.'</b>.';
        $order->addStatusHistoryComment($ckGiftcardComment);
        $this->orderRepository->save($order);
    }
}
