<?php

namespace CardknoxDevelopment\Cardknox\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class GiftCard extends Template
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * GiftCard constructor.
     *
     * @param Template\Context $context
     * @param CheckoutSession $checkoutSession
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
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

    /**
     * IsCardknoxGiftcardEnabled function
     *
     * @return boolean
     */
    public function isCardknoxGiftcardEnabled()
    {
        return $this->helper->isCardknoxGiftcardEnabled();
    }


    /**
     * CardknoxGiftcardText function
     *
     * @return boolean
     */
    public function cardknoxGiftcardText()
    {
        return $this->helper->cardknoxGiftcardText();
    }
}
