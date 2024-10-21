<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use CardknoxDevelopment\Cardknox\Model\TotalCalculator;
use Magento\Checkout\Model\Session as CheckoutSession;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;

class CKGiftcard extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var TotalCalculator
     */
    protected $totalCalculator;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Summary of __construct
     *
     * @param \CardknoxDevelopment\Cardknox\Model\TotalCalculator $totalCalculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \CardknoxDevelopment\Cardknox\Helper\Data $helper
     */
    public function __construct(
        TotalCalculator $totalCalculator,
        PriceCurrencyInterface $priceCurrency,
        CheckoutSession $checkoutSession,
        Helper $helper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->totalCalculator= $totalCalculator;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    /**
     * Collect function
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return void
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();

        // If there are no items in the shopping cart, return early
        if (empty($items) || !$this->helper->isCardknoxGiftcardEnabled()) {
            return $this;
        }

        // Get gift card balance

        $giftcardBalance = $this->checkoutSession->getCardknoxGiftCardBalance();
        $this->totalCalculator->collectQuoteCKGiftCard($quote, $total, $giftcardBalance);
        $baseTotalAmount = $this->totalCalculator->getBaseAmount();

        if ($baseTotalAmount > 0) {
            // Convert base total amount to the appropriate currency
            $convertedTotal = $this->priceCurrency->convertAndRound($baseTotalAmount);

            // Set gift card totals
            $total->setBaseTotalAmount('ckgiftcard', $baseTotalAmount);
            $total->setTotalAmount('ckgiftcard', $convertedTotal);

            // Save the original grand total
            $quote->setData('base_grand_total_without_ckgiftcard', $quote->getData('base_grand_total'));
            $quote->setData('grand_total_without_ckgiftcard', $quote->getData('grand_total'));

            // Update the grand total amounts
            $quote->setData('grand_total', max(0, $quote->getData('grand_total') - $convertedTotal));
            $quote->setData('base_grand_total', max(0, $quote->getData('base_grand_total') - $baseTotalAmount));
            $quote->save();

            // Update the total amounts
            $total->addTotalAmount('ckgiftcard', -$convertedTotal);
            $total->setTotalAmount('grand', $total->getGrandTotal() - $convertedTotal);
            $total->setBaseTotalAmount('grand', $total->getBaseGrandTotal() - $baseTotalAmount);

            // Set updated grand totals
            $total->setData('grand_total', $total->getGrandTotal() - $convertedTotal);
            $total->setData('base_grand_total', $total->getBaseGrandTotal() - $baseTotalAmount);

            $quote->setCkgiftcardCode($this->checkoutSession->getCardknoxGiftCardCode());
            $quote->setCkgiftcardAmount($convertedTotal);
            $quote->setCkgiftcardBaseAmount($convertedTotal);
            $quote->save();
        }

        return $this;
    }

    /**
     * Fetch the gift card totals.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        if ($this->helper->isCardknoxGiftcardEnabled()) {
            return [
                'code' => 'ckgiftcard',
                'title' => __('Gift Card'),
                'value' => $quote->getCkgiftcardAmount()
            ];
        }
        return null;
    }
}
