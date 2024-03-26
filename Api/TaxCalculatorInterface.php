<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Api;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Api\Data\TotalsInterface;

/**
 * Interface for tax calculation
 * @api
 */
interface TaxCalculatorInterface
{
    /**
     * Calculate tax based on address and shipping method.
     *
     * @param int $cartId
     * @param TotalsInformationInterface $addressInformation
     * @return TotalsInterface
     */
    public function calculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    );
}
