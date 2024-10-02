<?php

namespace CardknoxDevelopment\Cardknox\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteRepository;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class RedeemGiftCardObserver implements ObserverInterface
{
    /**
     *
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var \CardknoxDevelopment\Cardknox\Helper\Giftcard
     */
    protected $_giftcardHelper;

    /**
     *
     * @var Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * __construct function
     *
     * @param LoggerInterface $logger
     * @param Giftcard $giftcardHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param Helper $helper
     */
    public function __construct(
        LoggerInterface $logger,
        Giftcard $giftcardHelper,
        OrderRepositoryInterface $orderRepository,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        Helper $helper
    ) {
        $this->logger = $logger;
        $this->_giftcardHelper = $giftcardHelper;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
    }

    /**
     * Execute the observer to redeem the gift card after the order is placed.
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CKGiftcard_Redeem.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        try {
            $order = $observer->getEvent()->getOrder();
            $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
            if ($isCardknoxGiftcardEnabled) {

                $ckGiftCardCodeFromSession = $this->checkoutSession->getCardknoxGiftCardCode();
                $ckGiftCardAmountFromSession = $this->checkoutSession->getCardknoxGiftCardAmount();
    
                $quote = $this->quoteRepository->get($order->getQuoteId());
                $ckGiftCardCode = $quote->getCkgiftcardCode();
                $ckGiftCardAmount = $quote->getCkgiftcardAmount();
                $ckGiftCardBaseAmount = $quote->getCkgiftcardBaseAmount();
    
                $ckGiftCardAmountWithCurrency = $this->_giftcardHelper->getFormattedAmount($ckGiftCardAmount);
                $ckGiftcardComment = 'The Cardknox gift card with code <b>'.$ckGiftCardCode.'</b> has been successfully redeemed for an amount of <b>'.$ckGiftCardAmountWithCurrency.'</b>.';
    
                if ($ckGiftCardCode && $ckGiftCardAmount > 0) {
                    $this->redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order);
                    $order->addStatusHistoryComment($ckGiftcardComment);
                    $order->setCkgiftcardCode($ckGiftCardCode);
                    $order->setCkgiftcardAmount($ckGiftCardAmount);
                    $order->setCkgiftcardBaseAmount($ckGiftCardBaseAmount);
                    $this->orderRepository->save($order);
    
                    // Delete the gift card code and amount in the session
                    $this->checkoutSession->unsCardknoxGiftCardCode();
                    $this->checkoutSession->unsCardknoxGiftCardAmount();
                    $this->checkoutSession->unsCardknoxGiftCardBalance();
    
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Gift card redemption failed: ' . $e->getMessage());
        }
    }

    /**
     * Redeem the gift card by calling a third-party API
     *
     * @param string $giftCardCode
     * @param int $ckGiftCardAmount
     * @param string $orderIncrementId
     * @return void
     * @throws LocalizedException
     */
    private function redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CKGiftcard_Redeem.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $orderIncrementId = $order->getIncrementId();
        try {
            $apiResponse = $this->_giftcardHelper->redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order);
            if ($apiResponse['xStatus'] == "Approved") {
                $logger->info("SUCCESS:: Gift card amount redeem for order #".$orderIncrementId);
                $apiResponseData = [
                    "order_increment_id" => $orderIncrementId,
                    "giftcard_code" => $ckGiftCardCode,
                    "giftcard_amount" => $this->_giftcardHelper->getFormattedAmount($apiResponse['xAuthAmount'])
                ];
                $logger->info(print_r($apiResponseData, true));
            } elseif ($apiResponse['xStatus'] == "Error") {
                $logger->info("FAILED:: Gift card amount redeem for order #".$orderIncrementId);
                $logger->info("ERROR:: ".print_r($apiResponse['xError'], true));
                $apiResponseData = [
                    "order_increment_id" => $orderIncrementId,
                    "giftcard_code" => $ckGiftCardCode,
                    "giftcard_amount" => $this->_giftcardHelper->getFormattedAmount($apiResponse['xAuthAmount'])
                ];
                $logger->info(print_r($apiResponseData, true));
            }
        } catch (LocalizedException $e) {
            $logger->info(print_r($e->getMessage(), true));
        }
    }
}
