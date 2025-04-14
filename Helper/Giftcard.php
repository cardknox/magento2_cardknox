<?php

namespace CardknoxDevelopment\Cardknox\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use CardknoxDevelopment\Cardknox\Helper\Data as CardknoxDataHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Giftcard extends AbstractHelper
{
    public const CARDKNOX_API_URL = 'https://x1.cardknox.com/gatewayjson';
    public const CARDKNOX_TRANSACTION_KEY = 'payment/cardknox/cardknox_transaction_key';
    public const CARDKNOX_X_VERSION = "5.0.0";
    public const CONTENT_TYPE = "application/json";
    public const XSOFTWARE_VERSION = "1.2.75";

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var CardknoxDataHelper
     */
    protected $helper;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \CardknoxDevelopment\Cardknox\Helper\Data $helper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        Curl $curl,
        ProductMetadataInterface $productMetadata,
        PriceHelper $priceHelper,
        CardknoxDataHelper $helper,
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->curl = $curl;
        $this->productMetadata = $productMetadata;
        $this->priceHelper = $priceHelper;
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
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
            $this->productMetadata->getEdition(),
            $this->productMetadata->getVersion()
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
     * @param mixed $giftCardCode
     * @return mixed
     */
    public function checkGiftCardBalanceStatus($giftCardCode)
    {
        $headers = ["Content-Type" => self::CONTENT_TYPE];
        $this->curl->setHeaders($headers);

        $params = [
            "xCardNum" => $giftCardCode,
            "xKey" => $this->getTransactionKey(),
            "xVersion" => self::CARDKNOX_X_VERSION,
            "xSoftwareName" => $this->getMagentoEditionVersion(),
            "xSoftwareVersion" => self::XSOFTWARE_VERSION,
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
        $this->curl->post(self::CARDKNOX_API_URL, $giftcardBalanceParams);

        return $this->curl->getBody();
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

    /**
     * Redeem Gift Card
     *
     * @param mixed $ckGiftCardCode
     * @param mixed $ckGiftCardAmount
     * @param mixed $order
     * @return mixed
     */
    public function redeemGiftCard($ckGiftCardCode, $ckGiftCardAmount, $order)
    {
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $ipAddress = $this->helper->getIpAddress();

        $headers = ["Content-Type" => self::CONTENT_TYPE];
        $this->curl->setHeaders($headers);

        $params = [
            "xCardNum" => $ckGiftCardCode,
            "xKey" => $this->getTransactionKey(),
            "xSoftwareName" => $this->getMagentoEditionVersion(),
            "xSoftwareVersion" => self::XSOFTWARE_VERSION,
            'xVersion' => self::CARDKNOX_X_VERSION,
            'xIP' => $ipAddress ? $ipAddress : $order->getRemoteIp(),
            'xSupports64BitRefnum' => true,
            "xCommand" => "gift:redeem",
            "xAmount" =>  $ckGiftCardAmount,
            "xInvoice" =>  $order->getIncrementId(),
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
        $this->curl->post(self::CARDKNOX_API_URL, $giftcardRedeemParams);

        $response = $this->curl->getBody();
        return json_decode($response, true);
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
        return $this->priceHelper->currency(
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
        $shippingAddress = $quote->getShippingAddress();

        // Set the address data if not already set
        if (!$shippingAddress->getCountryId() || !$shippingAddress->getStreet()) {
            $shippingAddress->addData([
                'country_id' => 'US'
            ]);
        }

        // Set the shipping method
        $shippingAddress->setShippingMethod($selectedShippingMethod);
        $shippingAddress->setCollectShippingRates(true);

        // Collect shipping rates and totals
        $shippingAddress->collectShippingRates();
        $quote->collectTotals();

        // Save the quote with the new shipping method
        $this->quoteRepository->save($quote);
    }

    /**
     * Issue Gift Card while credit memo generated
     *
     * @param mixed $ckGiftCardAmount
     * @param mixed $order
     * @return mixed
     */
    public function giftAmountReIssue($ckGiftCardAmount, $order)
    {

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CKGiftcard_ReIssue.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $response = [];
        try {
            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();
            $ipAddress = $this->helper->getIpAddress();
            $ckGiftCardCode = $order->getCkgiftcardCode();
            $headers = ["Content-Type" => self::CONTENT_TYPE];
            $this->curl->setHeaders($headers);
            $xTimeoutSeconds = "10";

            // Billing Params
            $xBillFirstName = $billing->getFirstname();
            $xBillLastName = $billing->getLastname();
            $xBillCompany = $billing->getCompany();
            $xBillStreet = $billing->getStreetLine1();
            $xBillStreet2 = $billing->getStreetLine2();
            $xBillCity = $billing->getCity();
            $xBillState = $billing->getRegionCode();
            $xBillZip = $billing->getPostcode();
            $xBillCountry= $billing->getCountryId();
            $xBillPhone = $billing->getTelephone();
            $xEmail = $billing->getEmail();

            $params = [
                "xCardNum" => $ckGiftCardCode,
                "xKey" => $this->getTransactionKey(),
                "xSoftwareName" => $this->getMagentoEditionVersion(),
                "xSoftwareVersion" => self::XSOFTWARE_VERSION,
                'xVersion' => self::CARDKNOX_X_VERSION,
                'xIP' => $ipAddress ? $ipAddress : $order->getRemoteIp(),
                'xSupports64BitRefnum' => true,
                "xCommand" => "gift:issue",
                "xAmount" =>  $ckGiftCardAmount,
                "xInvoice" =>  $order->getIncrementId(),
                "xExistingCustomer" => "TRUE",
                "xTimeoutSeconds" => $xTimeoutSeconds,
                'xBillFirstName' => $xBillFirstName,
                'xBillLastName' => $xBillLastName,
                'xBillCompany' => $xBillCompany,
                'xBillStreet' => $xBillStreet,
                'xBillStreet2' => $xBillStreet2,
                'xBillCity' => $xBillCity,
                'xBillState' => $xBillState,
                'xBillZip' => $xBillZip,
                'xBillCountry'=> $xBillCountry,
                'xBillPhone' => $xBillPhone,
                'xEmail' => $xEmail,
            ];

            if ($shipping != "") {
                // Shipping Params
                $xShipFirstName = $shipping->getFirstname();
                $xShipLastName = $shipping->getLastname();
                $xShipCompany = $shipping->getCompany();
                $xShipStreet = $shipping->getStreetLine1();
                $xShipStreet2= $shipping->getStreetLine2();
                $xShipCity = $shipping->getCity();
                $xShipState = $shipping->getRegionCode();
                $xShipZip = $shipping->getPostcode();
                $xShipCountry = $shipping->getCountryId();

                $shippingParams = [
                    'xShipFirstName' => $xShipFirstName,
                    'xShipLastName' => $xShipLastName,
                    'xShipCompany' => $xShipCompany,
                    'xShipStreet' => $xShipStreet,
                    'xShipStreet2'=> $xShipStreet2,
                    'xShipCity' => $xShipCity,
                    'xShipState' => $xShipState,
                    'xShipZip' => $xShipZip,
                    'xShipCountry' => $xShipCountry,
                ];
            } else {
                $shippingParams = [];
            }

            $params = array_merge_recursive($params, $shippingParams);

            $giftcardIssueParams = json_encode($params);
            $this->curl->post(self::CARDKNOX_API_URL, $giftcardIssueParams);

            $response = $this->curl->getBody();
            $logger->info('Gift reissue sucessfully: ' . $response);
        } catch (\Exception $e) {
            $logger->info('Gift reissue failed: ' . $e->getMessage());
        }
        return json_decode($response, true);
    }

    /**
     * Gift:issue while credit memo|void generate function
     *
     * @param int|float|mixed $giftIssueAmount
     * @param mixed $order
     * @return void
     */
    public function giftIssue($giftIssueAmount, $order)
    {
        $result = $this->giftAmountReIssue($giftIssueAmount, $order);

        $ckGiftCardCode = $order->getCkgiftcardCode();
        $giftCardAmountWithCurrency = $this->getFormattedAmount($giftIssueAmount);
        $ckGiftcardComment = null;
        // Handle error status
        if ($result['xStatus'] === "Error") {
            $ckGiftcardComment = sprintf(
                'Gift issue failed. xErrorCode: %s, xError: %s',
                $result['xError'],
                $result['xError']
            );
        }
        // Handle approved status
        if ($result['xStatus'] === "Approved") {
            $ckGiftcardComment = sprintf(
                'Gift card %s has been successfully issued for an amount of %s. Transaction ID: %s.',
                $result['xMaskedCardNumber'],
                $giftCardAmountWithCurrency,
                $result['xRefNum'],
            );
        }
        if (!empty($ckGiftcardComment)) {
            $order->addStatusHistoryComment($ckGiftcardComment);
            $this->orderRepository->save($order);
        }
    }
}
