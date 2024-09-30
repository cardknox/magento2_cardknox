<?php

namespace CardknoxDevelopment\Cardknox\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;

class GiftCard extends Template
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * GiftCard constructor.
     *
     * @param Template\Context $context
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get the URL for adding the gift card.
     *
     * @return string
     */
    public function getAddGiftCardUrl()
    {
        return $this->getUrl('cardknox/giftcard/addGiftCard');
    }

    /**
     * Get the URL for applying the gift card.
     *
     * @return string
     */
    public function getCheckGiftCardBalanceUrl()
    {
        return $this->getUrl('cardknox/giftcard/checkBalanceStatus');
    }

    /**
     * Get cardknox giftcard amount from the checkout session function
     *
     * @return mixed|null
     */
    public function getGiftCardAmount()
    {
        // Store the gift card code and amount in the session
        return  $this->checkoutSession->getCardknoxGiftCardAmount();
    }

    /**
     * Get cardknox giftcard code from the checkout session
     *
     * @return mixed|null
     */
    public function getGiftCardCode()
    {
        return $this->checkoutSession->getCardknoxGiftCardCode();
    }

    /**
     * Get the URL for applying the gift card.
     *
     * @return string
     */
    public function getCancelGiftCardUrl()
    {
        return $this->getUrl('cardknox/giftcard/cancelGiftCard');
    }
}
