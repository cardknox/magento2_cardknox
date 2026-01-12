<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use CardknoxDevelopment\Cardknox\Helper\Data as DataHelper;

class CancelGiftCard extends AbstractGiftcardAction
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Giftcard $giftcardHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param DataHelper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Giftcard $giftcardHelper,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        DataHelper $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context, $resultJsonFactory, $giftcardHelper, $helper);
    }

    /**
     * Execute controller action to cancel gift card
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $errorResponse = $this->validateGiftcardEnabled();
        if ($errorResponse) {
            return $errorResponse;
        }

        $giftCardCode = $this->getGiftCardCode();
        if (!$giftCardCode) {
            return $this->createJsonResponse(false, __('Gift Card code is missing.'));
        }

        try {
            $quote = $this->checkoutSession->getQuote();

            $grandTotal = $quote->getGrandTotal();
            $giftCardAmount = $this->checkoutSession->getCardknoxGiftCardAmount();

            // Delete the gift card code and amount in the session
            $this->checkoutSession->unsCardknoxGiftCardCode($giftCardCode);
            $this->checkoutSession->unsCardknoxGiftCardAmount($giftCardAmount);
            $this->checkoutSession->unsCardknoxGiftCardBalance();

            // Adjust the quote totals
            $quote->setCkgiftcardCode(null);
            $quote->setCkgiftcardAmount(0);
            $quote->setCkgiftcardBaseAmount(0);
            $quote->setGrandTotal($grandTotal + $giftCardAmount);
            $quote->collectTotals();

            $this->quoteRepository->save($quote);

            return $this->createJsonResponse(true, __('Gift card has been cancelled successfully.'));
        } catch (LocalizedException $e) {
            return $this->createJsonResponse(false, $e->getMessage());
        }
    }
}
