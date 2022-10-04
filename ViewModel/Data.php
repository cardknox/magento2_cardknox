<?php
namespace CardknoxDevelopment\Cardknox\ViewModel;

class Data implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public const XPATH_FIELD_GOOGLEPAY_ENABLED = 'payment/cardknox_google_pay/cardknox_gpay_active';

    /**
     * __construct function
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }
 
    /**
     * Check google pay payment method enable function
     *
     * @return bool
     */
    public function isActiveGooglePay(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XPATH_FIELD_GOOGLEPAY_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?? false;
    }
}
