<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;

class ValidateGiftCard extends Action implements HttpPostActionInterface
{
    /**
     *
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     *
     * @var \CardknoxDevelopment\Cardknox\Helper\Giftcard
     */
    protected $giftcardHelper;

    /**
     * __construct function
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        Helper $helper,
        Giftcard $giftcardHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        $this->giftcardHelper = $giftcardHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        // Check if Cardknox GiftCard is enabled
        if (!$this->helper->isCardknoxGiftcardEnabled()) {
            return $this->createResponse(false, __('Please enable Cardknox GiftCard.'));
        }

        try {
            $quoteData = $this->getRequest()->getPost('quote_data');
            $selectedShippingMethod = $this->getRequest()->getPost('selected_shipping_method');
            $quote = $this->checkoutSession->getQuote();

            // Check if a gift card code is already applied to the quote
            if ($quote->getCkgiftcardCode()) {
                $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
                $shippingAmount = $quote->getShippingAddress()->getShippingAmount();

                if ($selectedShippingMethod &&
                    ($shippingMethod !== $selectedShippingMethod || $shippingAmount == 0)) {
                    $this->giftcardHelper->setShippingMethodForce(
                        $quote,
                        $selectedShippingMethod
                    );
                }
                // Check conditions for successful cancellation of the gift card
                if ($this->isGiftCardCancellationValid($quote, $quoteData)) {
                    return $this->createResponse(true, __('Gift card cancelled successfully.'));
                }
            }

            return $this->createResponse(false, __('No gift card code found to cancel.'));

        } catch (LocalizedException $e) {
            return $this->createResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            return $this->createResponse(false, __('An error occurred while processing your request.'));
        }
    }

    /**
     * Create a JSON response.
     *
     * @param bool $success
     * @param string $message
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function createResponse($success, $message)
    {
        return $this->resultJsonFactory->create()->setData([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * Validate conditions for gift card cancellation.
     *
     * @param $quote
     * @param array $quoteData
     * @return bool
     */
    private function isGiftCardCancellationValid($quote, array $quoteData)
    {
        return $quote->getGrandTotal() === 0.0
            && isset($quoteData['amount'])
            && $quoteData['amount'] == 0
            && $quote->getCouponCode();
    }
}
