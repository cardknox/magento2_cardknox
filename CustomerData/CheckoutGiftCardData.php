<?php
namespace CardknoxDevelopment\Cardknox\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;

class CheckoutGiftCardData implements SectionSourceInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * __construct function
     *
     * @param CheckoutSession $checkoutSession

     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get Section Data function
     *
     * @return array
     */
    public function getSectionData()
    {
        $ckGiftCardCode = null;
        $ckGiftCardAmount = null;
        // Retrieve custom data from the checkout session
        $ckGiftCardCode = $this->checkoutSession->getCardknoxGiftCardCode() ?? null;
        $ckGiftCardAmount = $this->checkoutSession->getCardknoxGiftCardAmount() ?? null;
 
        return [
            'ckgiftcard_code' => $ckGiftCardCode,
            'ckgiftcard_amount' => $ckGiftCardAmount
        ];
    }
}
