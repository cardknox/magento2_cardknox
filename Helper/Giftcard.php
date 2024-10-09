<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use CardknoxDevelopment\Cardknox\Helper\Data as CardknoxDataHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Helper\Context;

class Giftcard extends AbstractHelper
{
    public const CARDKNOX_API_URL = 'https://x1.cardknox.com/gatewayjson';
    public const CARDKNOX_TRANSACTION_KEY = 'payment/cardknox/cardknox_transaction_key';
    public const CARDKNOX_X_VERSION = "4.5.8";

    /**
     * @var Curl
     */
    private $_curl;

    /**
     * @var ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * @var CardknoxDataHelper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param Curl $curl
     * @param ProductMetadataInterface $productMetadata
     * @param PriceHelper $priceHelper
     * @param CardknoxDataHelper $helper
     */
    public function __construct(
        Context $context,
        Curl $curl,
        ProductMetadataInterface $productMetadata,
        PriceHelper $priceHelper,
        CardknoxDataHelper $helper
    ) {
        $this->_curl = $curl;
        $this->_productMetadata = $productMetadata;
        $this->_priceHelper = $priceHelper;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Get Magento edition and version
     *
     * @return string
     */
    public function getMagentoEditionVersion()
    {
        return sprintf(
            'Magento %s %s',
            $this->_productMetadata->getEdition(),
            $this->_productMetadata->getVersion()
        );
    }

    /**
     * Get Cardknox transaction key
     *
     * @return string|null
     */
    public function getTransactionKey()
    {
        return $this->scopeConfig->getValue(
            self::CARDKNOX_TRANSACTION_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Check gift card balance and status
     *
     * @return int
     */
    public function checkGiftCardBalanceStatus($giftCardCode)
    {
        $headers = ["Content-Type" => "application/json"];
        $this->_curl->setHeaders($headers);

        $params = [
            "xCardNum" => $giftCardCode,
            "xKey" => $this->getTransactionKey(),
            "xVersion" => self::CARDKNOX_X_VERSION,
            "xSoftwareName" => $this->getMagentoEditionVersion(),
            "xSoftwareVersion" => "1.0.27",
            "xCommand" => "gift:balance",
            "xExistingCustomer" => "TRUE",
            "xTimeoutSeconds" => "10",
            'xSupports64BitRefnum' => true
        ];

        $response = $this->sendGiftCardRequest($params);

        return $this->handleResponse($response);
    }

    /**
     * Send Gift Card Request
     *
     * @param array $params
     * @return mixed
     */
    private function sendGiftCardRequest(array $params)
    {
        $giftcardBalanceParams = json_encode($params);
        $this->_curl->post(self::CARDKNOX_API_URL, $giftcardBalanceParams);

        return $this->_curl->getBody();
    }

    /**
     * Handle Response
     *
     * @param mixed $response
     * @return mixed
     */
    private function handleResponse($response)
    {
        $responseBody = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid response format.'];
        }

        return $responseBody;
    }

    public function redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order)
    {
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $ipAddress = $this->helper->getIpAddress();

        $headers = ["Content-Type" => "application/json"];
        $this->_curl->setHeaders($headers);

        $params = [
            "xCardNum" => $ckGiftCardCode,
            "xKey" => $this->getTransactionKey(),
            "xSoftwareName" => $this->getMagentoEditionVersion(),
            "xSoftwareVersion" => "1.0.27",
            'xVersion' => '4.5.8',
            'xIP' => $ipAddress ? $ipAddress : $order->getRemoteIp(),
            'xSupports64BitRefnum' => true,
            "xCommand" => "gift:redeem",
            "xAmount" =>  $ckGiftCardAmount,
            "xOrderID" =>  $order->getIncrementId(),
            "xExistingCustomer" => "TRUE",
            "xTimeoutSeconds" => "10",
            'xBillFirstName' => $billing->getFirstname(),
            'xBillLastName' => $billing->getLastname(),
            'xBillCompany' => $billing->getCompany(),
            'xBillStreet' => $billing->getStreetLine1(),
            'xBillStreet2' => $billing->getStreetLine2(),
            'xBillCity' => $billing->getCity(),
            'xBillState' => $billing->getRegionCode(),
            'xBillZip' => $billing->getPostcode(),
            'xBillCountry'=> $billing->getCountryId(),
            'xBillPhone' => $billing->getTelephone()
        ];

        if ($shipping != "") {
            $shippingParams = [
            'xShipFirstName' => $shipping->getFirstname(),
            'xShipLastName' => $shipping->getLastname(),
            'xShipCompany' => $shipping->getCompany(),
            'xShipStreet' => $shipping->getStreetLine1(),
            'xShipStreet2'=> $shipping->getStreetLine2(),
            'xShipCity' => $shipping->getCity(),
            'xShipState' => $shipping->getRegionCode(),
            'xShipZip' => $shipping->getPostcode(),
            'xShipCountry' => $shipping->getCountryId(),
            'xEmail' => $billing->getEmail(),
            ];
        } else {
            $shippingParams = [];
        }

        $params = array_merge_recursive($params, $shippingParams);

        $giftcardRedeemParams = json_encode($params);
        $this->_curl->post(self::CARDKNOX_API_URL, $giftcardRedeemParams);

        $response = $this->_curl->getBody();
        $responseBody = json_decode($response, true);

        return $responseBody;
    }

    /**
     * Calculate Giftcard Amount function
     *
     * @param int|float|mixed|null $giftCardBalance
     * @param int|float|mixed|null $grandTotal
     * @return array
     */
    public function calculateGiftcardAmount($giftCardBalance, $grandTotal)
    {
        if (!is_numeric($giftCardBalance) || !is_numeric($grandTotal)) {
            throw new \InvalidArgumentException(__('Gift card balance and grand total must be numeric.'));
        }

        if ($giftCardBalance >= $grandTotal) {
            $appliedAmount = $grandTotal;
            $remainingGrandTotal = 0.00;
        } else {
            $appliedAmount = $giftCardBalance;
            $remainingGrandTotal = max(0.00, $grandTotal - $giftCardBalance);
        }

        return [
            'applied_amount' => $appliedAmount,
            'remaining_grand_total' => $remainingGrandTotal,
        ];
    }
    /**
     * Get Formatted Amount
     *
     * @param mixed $amount
     * @return mixed
     */
    public function getFormattedAmount($amount)
    {
        return $this->_priceHelper->currency(
            $amount,
            true,
            false
        );
    }

    /**
     * Set Shipping Method Force
     *
     * @param mixed $quote
     * @param mixed $selectedShippingMethod
     * @return void
     */
    public function setShippingMethodForce($quote, $selectedShippingMethod)
    {
        $quote->getShippingAddress()->setShippingMethod($selectedShippingMethod);
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
    }
}
