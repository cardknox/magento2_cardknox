<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

class CancelGiftCard extends Action
{
    /**
     *
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     *
     * @var \CardknoxDevelopment\Cardknox\Helper\Giftcard
     */
    protected $_giftcardHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * __construct function
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     * @param Giftcard $giftcardHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        Giftcard $giftcardHelper,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_curl = $curl;
        $this->_giftcardHelper = $giftcardHelper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $giftCardCode = $this->getRequest()->getParam('giftcard_code');

        if (!$giftCardCode) {
            return $result->setData([
                'success' => false,
                'message' => __('Gift Card code is missing.'),
            ]);
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
            
            $message = "Gift card cancelled successfully.";
            return $result->setData([
                'success' => true,
                'message' => $message
            ]);

        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
