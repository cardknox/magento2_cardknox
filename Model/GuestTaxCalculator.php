<?php

namespace CardknoxDevelopment\Cardknox\Model;

use CardknoxDevelopment\Cardknox\Api\GuestTaxCalculatorInterface;
use CardknoxDevelopment\Cardknox\Api\TaxCalculatorInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Api\Data\TotalsInformationInterface;

class GuestTaxCalculator implements GuestTaxCalculatorInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var TaxCalculatorInterface
     */
    protected $taxCalculatorManagement;

    /**
     * __construct function
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param TaxCalculatorInterface $taxCalculatorManagement
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TaxCalculatorInterface $taxCalculatorManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->taxCalculatorManagement = $taxCalculatorManagement;
    }

    /**
     * @inheritDoc
     */
    public function calculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->taxCalculatorManagement->calculate(
            $quoteIdMask->getQuoteId(),
            $addressInformation
        );
    }
}
