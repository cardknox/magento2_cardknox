<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class CancelGiftCard extends Action
{
    /**
     *
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

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
     * @var Helper
     */
    protected $helper;

    /**
     * __construct function
     *
     * @param Context $context
     * @param ResultJsonFactory $resultJsonFactory
     * @param Giftcard $giftcardHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        ResultJsonFactory $resultJsonFactory,
        Giftcard $giftcardHelper,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        Helper $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_giftcardHelper = $giftcardHelper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Execute controller action to cancel gift card
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
        if (!$isCardknoxGiftcardEnabled) {
            return $result->setData([
                'success' => false,
                'message' => __('Please enable Sola Gift.'),
            ]);
        }

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

            $message = "Gift card has been cancelled successfully.";
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
