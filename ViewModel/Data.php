<?php
namespace CardknoxDevelopment\Cardknox\ViewModel;

class Data implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public const XPATH_FIELD_GOOGLEPAY_ENABLED = 'payment/cardknox_google_pay/active';
    public const XPATH_FIELD_CC_SELECT_RECAPTCHA_SOURCE = 'payment/cardknox/select_recaptcha_source';
    public const IS_ENABLE_GOOGLE_REPCAPTCHA = "payment/cardknox/recaptchaEnabled";
    public const APPLEPAY_ENABLE_ON_CARTPAGE = 'payment/cardknox_apple_pay/cart_page_enable';


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

    /**
     * Cardknox Google Recaptcha enable function
     *
     * @return boolean
     */
    public function isEnabledReCaptcha(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::IS_ENABLE_GOOGLE_REPCAPTCHA,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?? false;
    }

    /**
     * Select recaptcha source function
     *
     * Either google.com or recaptcha.net
     *
     * @return string
     */
    public function getSelectReCaptchaSource(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XPATH_FIELD_CC_SELECT_RECAPTCHA_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?? false;
    }

    /**
     * Check Cardknox ApplePay enable function
     *
     * @return boolean
     */
    public function isEnabledApplyPayOnCartPage(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::APPLEPAY_ENABLE_ON_CARTPAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?? false;
    }
}
