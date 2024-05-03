<?php

namespace CardknoxDevelopment\Cardknox\Model;

use CardknoxDevelopment\Cardknox\Api\GuestGoogleApplePayTaxCalcInterface;
use CardknoxDevelopment\Cardknox\Api\CustomerGoogleApplePayTaxCalcInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Api\Data\TotalsInformationInterface;

class GuestGoogleApplePayTaxCalc implements GuestGoogleApplePayTaxCalcInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CustomerGoogleApplePayTaxCalcInterface
     */
    protected $walletTaxCalcManagement;

    /**
     * __construct function
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CustomerGoogleApplePayTaxCalcInterface $walletTaxCalcManagement
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CustomerGoogleApplePayTaxCalcInterface $walletTaxCalcManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->walletTaxCalcManagement = $walletTaxCalcManagement;
    }

    /**
     * @inheritDoc
     */
    public function walletTaxCalculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->walletTaxCalcManagement->walletTaxCalculate(
            $quoteIdMask->getQuoteId(),
            $addressInformation
        );
    }
}
