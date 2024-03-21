<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Api;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Api\Data\TotalsInterface;

/**
 * Interface for guest tax calculation
 * @api
 */
interface GuestTaxCalculatorInterface
{
    /**
     * Calculate quote totals based on address and shipping method.
     *
     * @param string $cartId
     * @param TotalsInformationInterface $addressInformation
     * @return TotalsInterface
     */
    public function calculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    );
}
