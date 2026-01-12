<?php
namespace CardknoxDevelopment\Cardknox\Plugin\Controller\Cart;

use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class CouponPost
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CouponFactory
     */
    private $couponFactory;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * Constructor to inject dependencies
     *
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param CouponFactory $couponFactory
     * @param Escaper $escaper
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        CouponFactory $couponFactory,
        Escaper $escaper,
        MessageManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;
        $this->escaper = $escaper;
        $this->messageManager = $messageManager;
    }

    /**
     * After plugin for execute method in CouponPost controller
     *
     * @param CouponPost $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\CouponPost $subject,
        ResultInterface $result
    ) {
        // Get the quote from the checkout session
        $cartQuote = $this->checkoutSession->getQuote();
        $couponCode = $subject->getRequest()->getParam('coupon_code');
        $coupon = $this->couponFactory->create()->load($couponCode, 'code');
        $grandTotal = $cartQuote->getGrandTotal();

        // Apply coupon and handle gift card logic after execute
        if ($couponCode && $coupon->getId()) {
            $this->applyCouponAndHandleGiftCard($cartQuote, $grandTotal);
        }

        return $result;
    }

    /**
     * Applies coupon and handles gift card when grand total is zero
     *
     * @param \Magento\Quote\Model\Quote $cartQuote
     * @param float|mixed $grandTotal
     * @return void
     */
    private function applyCouponAndHandleGiftCard($cartQuote, $grandTotal)
    {
        $giftCardCode = $cartQuote->getCkgiftcardCode();

        if ((float)$grandTotal === 0.0 && (float)$cartQuote->getShippingAddress()->getShippingAmount() === 0.0) {
            // Unset gift card code and amounts only if both grand total and shipping amount are zero
            $this->checkoutSession->unsCardknoxGiftCardCode($giftCardCode);
            $this->checkoutSession->unsCardknoxGiftCardAmount();
            $this->checkoutSession->unsCardknoxGiftCardBalance();

            $cartQuote->setCkgiftcardCode(null);
            $cartQuote->setCkgiftcardAmount(0);
            $cartQuote->setCkgiftcardBaseAmount(0);

            // Adjust grand total by adding back the gift card amount
            $cartQuote->setGrandTotal($grandTotal + $this->checkoutSession->getCardknoxGiftCardAmount());
            $cartQuote->collectTotals(); // Recalculate totals after setting values

            // Save the updated quote
            $this->quoteRepository->save($cartQuote);
        }
    }
}
