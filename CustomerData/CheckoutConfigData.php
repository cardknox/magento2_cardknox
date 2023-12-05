<?php
namespace CardknoxDevelopment\Cardknox\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\App\Request\Http;

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
     * __construct function
     *
     * @param Cart $cartModel
     * @param Onepage $onepageBlock
     * @param CartHelper $cartHelper
     * @param Http $httpRequest
     */
    public function __construct(
        Cart $cartModel,
        Onepage $onepageBlock,
        CartHelper $cartHelper,
        Http $httpRequest
    ) {
        $this->_cartModel = $cartModel;
        $this->_onepageBlock = $onepageBlock;
        $this->_cartHelper = $cartHelper;
        $this->_httpRequest = $httpRequest;
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
}
