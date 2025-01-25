<?php
namespace CardknoxDevelopment\Cardknox\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Session as CheckoutSession;

class CheckoutConfigData implements SectionSourceInterface
{
    /**
     * @var Cart
     */
    protected $_cartModel;

    /**
     * @var Onepage
     */
    protected $_onepageBlock;

    /**
     * @var CartHelper
     */
    protected $_cartHelper;

    /**
     * @var Http
     */
    protected $_httpRequest;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * __construct function
     *
     * @param Cart $cartModel
     * @param Onepage $onepageBlock
     * @param CartHelper $cartHelper
     * @param Http $httpRequest
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Cart $cartModel,
        Onepage $onepageBlock,
        CartHelper $cartHelper,
        Http $httpRequest,
        CheckoutSession $checkoutSession
    ) {
        $this->_cartModel = $cartModel;
        $this->_onepageBlock = $onepageBlock;
        $this->_cartHelper = $cartHelper;
        $this->_httpRequest = $httpRequest;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get Current QuoteId function
     *
     * @return mixed|int|null
     */
    public function getCurrentQuoteId()
    {
        return $this->_cartModel->getQuote()->getId();
    }

    /**
     * Get Cart Item Count function
     *
     * @return mixed|int|null
     */
    public function getCartItemCount()
    {
        return $this->_cartHelper->getItemsCount();
    }

    /**
     * Get Module Name function
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_httpRequest->getModuleName();
    }

    /**
     * Get Section Data function
     *
     * @return array
     */
    public function getSectionData()
    {
        $cartId = $this->getCurrentQuoteId();
        $itemCount = $this->getCartItemCount();
        $moduleName = $this->getModuleName();
        $quote = $this->_cartModel->getQuote();

        if ($this->isQuoteEmpty($quote)) {
            $ckGiftCardCode = $quote->setCkgiftcardCode("");
            $ckGiftCardAmount = $quote->setCkgiftcardAmount("");
            $ckGiftCardBaseAmount = $quote->setCkgiftcardBaseAmount("");
            $quote->save();

            $this->checkoutSession->unsCardknoxGiftCardCode();
            $this->checkoutSession->unsCardknoxGiftCardAmount();
            $this->checkoutSession->unsCardknoxGiftCardBalance();
        }
        $checkoutData = '';
        if ($cartId && $itemCount > 0 && $moduleName != 'checkout') {
            $serializedCheckoutConfig = $this->_onepageBlock->getSerializedCheckoutConfig();
            $checkoutData = "<script> window.checkoutConfig = ". $serializedCheckoutConfig ."; </script>";
        }

        return [
            'cart_id' => $itemCount,
            'config_data' => $checkoutData
        ];
    }

    /**
     * Check if the quote is empty function
     *
     * @param mixed $quote
     * @return bool
     */
    public function isQuoteEmpty($quote): bool
    {
        // Check if there are no items in the quote
        return !$quote->hasItems();
    }
}
