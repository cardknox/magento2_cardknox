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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Giftcard
     */
    protected $_giftcardHelper;

    /**
     * @var OrderRepositoryInterface
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
     * Constructor
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
        try {
            $order = $observer->getEvent()->getOrder();
            $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
            if ($isCardknoxGiftcardEnabled) {
                $quote = $this->quoteRepository->get($order->getQuoteId());
                $ckGiftCardCode = $quote->getCkgiftcardCode();
                $ckGiftCardAmount = $quote->getCkgiftcardAmount();
                $ckGiftCardBaseAmount = $quote->getCkgiftcardBaseAmount();

                $ckGiftCardAmountWithCurrency = $this->_giftcardHelper->getFormattedAmount($ckGiftCardAmount);

                if ($ckGiftCardCode && $ckGiftCardAmount > 0) {
                    $result = $this->redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order);
                    $ckGiftcardComment = null;

                    // Handle error status
                    if ($result['xStatus'] === "Error") {
                        $ckGiftcardComment = sprintf(
                            'The Cardknox gift card redeem error occurred. xErrorCode: %s, xError: %s',
                            $result['xError'],
                            $result['xError']
                        );
                    }

                    // Handle approved status
                    if ($result['xStatus'] === "Approved") {
                        $ckGiftcardComment = sprintf(
                            'Gift Card Authorized amount of %s. %s. Transaction ID: %s.',
                            $ckGiftCardAmountWithCurrency,
                            $result['xMaskedCardNumber'],
                            $result['xRefNum'],
                        );
                        $order->setCkgiftcardCode($ckGiftCardCode)
                              ->setCkgiftcardAmount($ckGiftCardAmount)
                              ->setCkgiftcardBaseAmount($ckGiftCardBaseAmount);
                    }
                    // Add comment to ticket
                    if (!empty($ckGiftcardComment)) {
                        $order->addStatusHistoryComment($ckGiftcardComment);
                    }
                    // Save the order
                    $this->orderRepository->save($order);

                    // Delete the gift card code and amount in the session
                    $this->checkoutSession->unsCardknoxGiftCardCode();
                    $this->checkoutSession->unsCardknoxGiftCardAmount();
                    $this->checkoutSession->unsCardknoxGiftCardBalance();

                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Gift card redemption failed', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Redeem the gift card by calling a third-party API
     *
     * @param mixed|string $ckGiftCardCode
     * @param mixed|int|float $ckGiftCardAmount
     * @param mixed|string $order
     * @return array
     * @throws LocalizedException
     */
    private function redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order)
    {
        $orderIncrementId = $order->getIncrementId();
        $apiResponse = [];
        try {
            $apiResponse = $this->_giftcardHelper->redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order);

            $this->logger->info('Gift card redemption API response', [
                'order_id' => $orderIncrementId,
                'status' => $apiResponse['xStatus'] ?? 'unknown',
                'response' => $apiResponse
            ]);

            if ($apiResponse['xStatus'] == "Approved") {
                $this->logger->info('Gift card redemption successful', [
                    'order_id' => $orderIncrementId,
                    'gift_card_code' => $ckGiftCardCode,
                    'amount_redeemed' => $this->_giftcardHelper->getFormattedAmount($apiResponse['xAuthAmount'])
                ]);
            } elseif ($apiResponse['xStatus'] == "Error") {
                $this->logger->error('Gift card redemption failed', [
                    'order_id' => $orderIncrementId,
                    'gift_card_code' => $ckGiftCardCode,
                    'error' => $apiResponse['xError'] ?? 'Unknown error',
                    'amount_attempted' => $this->_giftcardHelper->getFormattedAmount(
                        $apiResponse['xAuthAmount'] ?? 0
                    )
                ]);
            }

            return $apiResponse;
        } catch (LocalizedException $e) {
            $this->logger->error('Gift card redemption exception', [
                'order_id' => $orderIncrementId,
                'exception' => $e->getMessage()
            ]);
            return $apiResponse;
        }
    }
}
