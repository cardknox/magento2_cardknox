<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
    
    /**
     * Format price to 0.00 format
     *
     * @param mixed $price
     * @return string
     * @since 100.1.0
     */
    public function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }
}
