<?php
namespace CardknoxDevelopment\Cardknox\Observer;

use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class QuoteSubmitBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * __construct function
     *
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }
    /**
     * Convert giftcard amount quote to order function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
        $quote = $observer->getQuote();
        if ($isCardknoxGiftcardEnabled &&
            $quote->getData('ckgiftcard_amount') &&
            $quote->getData('ckgiftcard_base_amount')
        ) {
            $order = $observer->getOrder();
            $order->setData('ckgiftcard_amount', $quote->getData('ckgiftcard_amount'));
            $order->setData('ckgiftcard_base_amount', $quote->getData('ckgiftcard_base_amount'));
        }
    }
}
