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

class AddGiftCard extends Action
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
                'message' => __('Gift Card code is required.'),
            ]);
        }
        try {
            $apiResponse = $this->_giftcardHelper->checkGiftCardBalanceStatus($giftCardCode);

            if ($apiResponse['xStatus'] == "Approved" &&
                $apiResponse['xActivationStatus'] == "Active" &&
                $apiResponse['xRemainingBalance'] > 0 )
            {
                $apiResponseData = [
                    "xRemainingBalance" => $apiResponse['xRemainingBalance'],
                    "xActivationStatus" => $apiResponse['xActivationStatus']
                ];
                $giftCardBalance = $apiResponse['xRemainingBalance'];
                $quote = $this->checkoutSession->getQuote();

                // Log the initial grand total before collecting totals
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Before collecting totals: Grand Total: ' . $quote->getGrandTotal());

                $grandTotal = $quote->getGrandTotal();
                $calculateGiftcardAmount = $this->_giftcardHelper->calculateGiftcardAmount($giftCardBalance, $grandTotal);
                $appliedAmount = $calculateGiftcardAmount['applied_amount'];

                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Gift Card Amount: ' . $giftCardBalance);
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Applied Gift Card Amount: ' . $appliedAmount);


                // Store the gift card code and amount in the session
                $this->checkoutSession->setCardknoxGiftCardCode($giftCardCode);
                $this->checkoutSession->setCardknoxGiftCardAmount($appliedAmount);
                $this->checkoutSession->setCardknoxGiftCardBalance($giftCardBalance);

                // Log the grand total after collecting totals but before applying the gift card
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('After collecting totals, before gift card: Grand Total: ' . $quote->getGrandTotal());

                // Adjust the quote totals
                $quote->setCkgiftcardCode($giftCardCode);
                $quote->setCkgiftcardAmount($appliedAmount);
                $quote->setCkgiftcardBaseAmount($appliedAmount);
                $quote->collectTotals();

                $this->quoteRepository->save($quote);

                // Log the grand total after applying the gift card
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('After applying gift card: Grand Total: ' . $quote->getGrandTotal());

                $appliedAmountWithCurrency = $this->_giftcardHelper->getFormattedAmount($appliedAmount);
                $message = "The gift card was applied successfully with an amount of $appliedAmountWithCurrency";
                return $result->setData([
                    'success' => true,
                    'message' => $message,
                    'data' => $apiResponseData
                ]);
            } elseif ($apiResponse['xActivationStatus'] == 'Inactive') {
                return $result->setData([
                    'success' => false,
                    'message' => "Your gift card account is inactive. Please activate it before proceeding."
                ]);
            } elseif ($apiResponse['xRemainingBalance'] == 0) {
                return $result->setData([
                    'success' => false,
                    'message' => "Your gift card balance is zero. Please use another card number or credit your balance."
                ]);
            } elseif ($apiResponse['xStatus'] == "Error") {
                return $result->setData([
                    'success' => false,
                    'message' => $apiResponse['xError'],
                ]);
            }
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}