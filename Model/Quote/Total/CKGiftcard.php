<?php
namespace CardknoxDevelopment\Cardknox\Model\Quote\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use CardknoxDevelopment\Cardknox\Model\TotalCalculator;
use Magento\Checkout\Model\Session as CheckoutSession;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

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
     * __construct function
     *
     * @param TotalCalculator $totalCalculator
     * @param PriceCurrencyInterface $priceCurrency
     * @param CheckoutSession $checkoutSession
     * @param Helper $helper
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
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {

        parent::collect($quote, $shippingAssignment, $total);

        $address = $shippingAssignment->getShipping()->getAddress();
        $items = $shippingAssignment->getItems();

        //if there is no item in shopping cart then return
        if (!count($items)) {
            return $this;
        }
        $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();

        if (!$isCardknoxGiftcardEnabled) {
            return $this;
        }

        if ($quote) {
            $giftcardBalance = 0;
            $giftcardBalance = $this->checkoutSession->getCardknoxGiftCardBalance();
            $this->totalCalculator->collectQuoteCKGiftCard($quote, $total, $giftcardBalance);
            $baseTotalAmount = $this->totalCalculator->getBaseAmount();

            $convertedTotal = $this->priceCurrency->convertAndRound($baseTotalAmount);
            if ($baseTotalAmount) {
                $total->setBaseTotalAmount('ckgiftcard', $baseTotalAmount);
                $total->setTotalAmount('ckgiftcard', $convertedTotal);

                $quote->setData('ckgiftcard_base_amount', $baseTotalAmount);
                $quote->setData('ckgiftcard_amount', $convertedTotal);

                //save the original grand total
                $quote->setData('base_grand_total_without_ckgiftcard', $quote->getData('base_grand_total'));
                $quote->setData('grand_total_without_ckgiftcard', $quote->getData('grand_total'));

                $quoteBaseGrandTotal = max(0, $quote->getData('base_grand_total')- $baseTotalAmount);
                $quoteGrandTotal = max(0, $quote->getData('grand_total')- $convertedTotal);
                $quote->setData('grand_total', $quoteBaseGrandTotal);
                $quote->setData('base_grand_total', $quoteGrandTotal);
                $quote->save();

                $total->addTotalAmount('ckgiftcard', -$convertedTotal);
                $total->setTotalAmount('grand', $total->getGrandTotal()-$convertedTotal);
                $total->setBaseTotalAmount('grand', $total->getBaseGrandTotal() -$baseTotalAmount);

                $total->setData('grand_total', $total->getGrandTotal()-$convertedTotal);
                $total->setData('base_grand_total', $total->getBaseGrandTotal() -$baseTotalAmount);
            }
        }

        return $this;
    }

    /**
     * Fetch function
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array|null|mixed
     */
    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
        if ($isCardknoxGiftcardEnabled) {
            return [
                'code' => 'ckgiftcard',
                'title' => __('Gift Card'),
                'value' => -$quote->getCkgiftcardAmount()
            ];
        }
    }
}
