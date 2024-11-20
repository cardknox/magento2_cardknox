<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Checkout\Model\Session as CheckOutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use CardknoxDevelopment\Cardknox\Helper\Data;

class AddGiftCard extends Action
{
    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     *
     * @var \CardknoxDevelopment\Cardknox\Helper\Giftcard
     */
    protected $giftcardHelper;

    /**
     * @var CheckOutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \CardknoxDevelopment\Cardknox\Helper\Data
     */
    protected $helper;

    /**
     * __construct function
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Giftcard $giftcardHelper
     * @param CheckOutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Giftcard $giftcardHelper,
        CheckOutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->giftcardHelper = $giftcardHelper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Add GIFT Card
     *
     * @return mixed
     */
    public function execute()
    {
        if (!$this->helper->isCardknoxGiftcardEnabled()) {
            return $this->jsonResponse(false, __('Please enable Cardknox GiftCard.'));
        }

        $giftCardCode = $this->getRequest()->getParam('giftcard_code');
        $isCartPage = $this->getRequest()->getParam('is_cart_page');
        $selectedShippingMethod = $this->getRequest()->getParam('selected_method');

        if (!$giftCardCode) {
            return $this->jsonResponse(false, __('Gift Card code is required.'));
        }

        try {
            $quote = $this->checkoutSession->getQuote();

            // Validate quote
            if (!$quote || !is_object($quote)) {
                return $this->jsonResponse(false, __('We couldn\'t retrieve your cart details. Please try again.'));
            }

            if ($isCartPage && !$quote->isVirtual()) {
                $this->updateShippingMethod($quote, $selectedShippingMethod);
            }

            if ($this->isCartTotalZero($quote)) {
                return $this->jsonResponse(false, __('Your cart total is zero, so there\'s no need to apply a Gift Card code.'));
            }

            $apiResponse = $this->giftcardHelper->checkGiftCardBalanceStatus($giftCardCode);

            return $this->handleApiResponse($apiResponse, $giftCardCode, $quote);

        } catch (LocalizedException $e) {
            return $this->jsonResponse(false, $e->getMessage());
        }
    }

    /**
     * Update the shipping method of the quote
     *
     * @param mixed $quote
     * @param mixed $selectedShippingMethod
     * @return void
     */
    private function updateShippingMethod($quote, $selectedShippingMethod)
    {
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $shippingAmount = $quote->getShippingAddress()->getShippingAmount();

        if ($selectedShippingMethod &&
            ($shippingMethod !== $selectedShippingMethod || $shippingAmount == 0)) {
            $this->giftcardHelper->setShippingMethodForce(
                $quote,
                $selectedShippingMethod
            );
        }
    }

    /**
     * Check if the cart total is zero
     *
     * @param mixed $quote
     * @return bool
     */
    private function isCartTotalZero($quote)
    {
        return (float)$quote->getGrandTotal() === 0.0 && $quote->getShippingAddress()->getShippingAmount() == 0;
    }

    /**
     * Handle the API response for gift card validation.
     *
     * @param mixed $apiResponse
     * @param mixed $giftCardCode
     * @param mixed $quote
     * @return mixed
     */
    private function handleApiResponse($apiResponse, $giftCardCode, $quote)
    {
        $apiErrorMessage = $apiResponse['xError'] ?? null;
        $status = $apiResponse['xStatus'] ?? null;
        $activationStatus = $apiResponse['xActivationStatus'] ?? null;
        $remainingBalance = $apiResponse['xRemainingBalance'] ?? null;

        if ($status === "Approved" && $activationStatus === "Active" && $remainingBalance > 0) {
            return $this->applyGiftCard($apiResponse, $giftCardCode, $quote);
        }

        if ($activationStatus === 'Inactive') {
            return $this->jsonResponse(false, __('Your gift card account is inactive. Please activate it before proceeding.'));
        }

        if ((float)$remainingBalance === 0.0) {
            return $this->jsonResponse(false, __('Your gift card balance is zero. Please use another card number or credit your balance.'));
        }

        if ($status === "Error") {
            return $this->jsonResponse(false, $apiErrorMessage);
        }

        return $this->jsonResponse(false, __('An unexpected error occurred.'));
    }

    /**
     * Apply the gift card to the quote
     *
     * @param mixed $apiResponse
     * @param mixed $giftCardCode
     * @param mixed $quote
     * @return mixed
     */
    private function applyGiftCard($apiResponse, $giftCardCode, $quote)
    {
        $giftCardBalance = $apiResponse['xRemainingBalance'];
        $grandTotal = $quote->getGrandTotal();
        $calculateGiftcardAmount = $this->giftcardHelper->calculateGiftcardAmount($giftCardBalance, $grandTotal);
        $appliedAmount = $calculateGiftcardAmount['applied_amount'];

        // Store the gift card code and amount in the session
        $this->checkoutSession->setCardknoxGiftCardCode($giftCardCode);
        $this->checkoutSession->setCardknoxGiftCardAmount($appliedAmount);
        $this->checkoutSession->setCardknoxGiftCardBalance($giftCardBalance);

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();

        $this->quoteRepository->save($quote);

        $appliedAmountWithCurrency = $this->giftcardHelper->getFormattedAmount($appliedAmount);
        $message = __("The gift card was applied successfully with an amount of %1", $appliedAmountWithCurrency);

        return $this->jsonResponse(true, $message, [
            "xRemainingBalance" => $giftCardBalance,
            "xActivationStatus" => $apiResponse['xActivationStatus']
        ]);
    }

    /**
     * Generate a JSON response
     *
     * @param mixed $success
     * @param mixed $message
     * @param mixed $data
     * @return mixed
     */
    private function jsonResponse($success, $message, $data = [])
    {
        return $this->resultJsonFactory->create()->setData(array_merge(['success' => $success, 'message' => $message], $data));
    }
}
