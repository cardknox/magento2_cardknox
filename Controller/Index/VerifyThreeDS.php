<?php
namespace CardknoxDevelopment\Cardknox\Controller\Index;

use CardknoxDevelopment\Cardknox\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Payment\Model\Method\Logger;

class VerifyThreeDS extends Action implements HttpPostActionInterface
{
    private const GETWAY_HOST = 'https://x1.cardknox.com';
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var quoteManagement
     */
    protected $quoteManagement;

    /**
     * @var cartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AdditionalInformationMapping variable
     *
     * @var array
     */
    protected $additionalInformationMapping = [
        'xMaskedCardNumber',
        'xAvsResult',
        'xCvvResult',
        'xCardType',
        'xExp',
        'xBatch',
        'xRefNum',
        'xAuthCode',
        'xAvsResultCode',
        'xCvvResultCode',
        'xAuthAmount'
    ];

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \CardknoxDevelopment\Cardknox\Gateway\Config\Config $config
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \CardknoxDevelopment\Cardknox\Helper\Data $helper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Payment\Model\Method\Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        ProductMetadataInterface $productMetadata,
        Data $helper,
        QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->productMetadata = $productMetadata;
        $this->helper = $helper;
        $this->quoteManagement = $quoteManagement;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->logger = $logger;
    }

    /**
     * Get country region function
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $postData = $this->getRequest()->getPostValue();
        
        if (empty($postData)) {
            return $this->createErrorResponse(__('No data received in the request.'), 'checkout/cart');
        }

        $newPostData = $this->baseRequestParams($postData);
        $endpoint = self::GETWAY_HOST . '/verify';

        try {
            $log = [
                'request' => $newPostData,
                'request_uri' => $endpoint
            ];
            $response = $this->sendPostRequest($endpoint, $newPostData);
            $parsedResponse = $this->parseResponse($response);
            $log['response'] = $parsedResponse;

            $storeId = $this->getStoreIdFromQuote();
            $isDebug = $this->config->getValue(
                'cardknox_transaction_key',
                $storeId
            );
            if ($isDebug) {
                $this->logger->debug($log, null, true);
            }

            if ($this->isErrorResponse($parsedResponse)) {
                return $this->createErrorResponse(
                    $parsedResponse['xError'] ?? __('An error occurred.'),
                    'checkout/cart'
                );
            }

            if ($this->isApprovedResponse($parsedResponse)) {
                return $this->processApprovedResponse($parsedResponse);
            }

            return $this->createErrorResponse(__('Unexpected response format.'), 'checkout/cart');
        } catch (\Exception $e) {
            return $this->createErrorResponse(__('Error: %1', $e->getMessage()), 'checkout/cart');
        }
    }

    /**
     * Sends a POST request to the given endpoint with provided data.
     *
     * @param string $endpoint
     * @param array $data
     * @return string
     * @throws \Exception
     */
    private function sendPostRequest($endpoint, $data)
    {
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->post($endpoint, $data);
        return $this->curl->getBody();
    }

    /**
     * Parses the response string into an associative array.
     *
     * @param string $response
     * @return array
     */
    private function parseResponse($response)
    {
        parse_str($response, $parsedResponse);
        return $parsedResponse;
    }

    /**
     * Checks if the response indicates an error.
     *
     * @param array $response
     * @return bool
     */
    private function isErrorResponse($response)
    {
        return isset($response['xResult']) && $response['xResult'] === 'E';
    }

    /**
     * Checks if the response is approved.
     *
     * @param array $response
     * @return bool
     */
    private function isApprovedResponse($response)
    {
        return isset($response['xResult']) && $response['xResult'] === 'A';
    }

    /**
     * Handles the approved response logic.
     *
     * @param array $response
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Exception
     */
    private function processApprovedResponse($response)
    {
        $this->_checkoutSession->setSkipValidation(true);
        $quote = $this->_checkoutSession->getQuote();

        // Set the custom reserved order ID and save the quote
        $quote->setReservedOrderId($response['xInvoice']);
        $quote->save();

        // Place the order and retrieve the order details
        $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
        $order = $this->orderFactory->create()->load($orderId);
        $this->updateOrderDetails($order, $response);

        return $this->createSuccessResponse(
            __('Thank you for your purchase!'),
            'checkout/onepage/success',
            $order
        );
    }

    /**
     * Updates order details such as increment ID and session data.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $response
     * @return void
     */
    private function updateOrderDetails($order, $response)
    {
        $incrementId = $response['xInvoice'];
        $order->setIncrementId($incrementId);
        $order->setEmailSent(0);

        // Set transaction ID and additional information
        $payment = $order->getPayment();
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response["xRefNum"]);
        $payment->setLastTransId($response["xRefNum"]);
        $payment->setIsTransactionClosed(false);
        // if ($payment->getLastTransId() == '') {
            foreach ($this->additionalInformationMapping as $item) {
                if (!isset($response[$item])) {
                    continue;
                }
                $payment->setAdditionalInformation($item, $response[$item]);
            }
        /*} else {
            if (isset($response["xBatch"])) {
                //batch only gets added after capturing
                $payment->setAdditionalInformation("xBatch", $response["xBatch"]);
            }
        }*/
        $payment->save();
        $order->save();

        // Set session data for success page
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }

    /**
     * Creates a success response with a redirect.
     *
     * @param string $message
     * @param string $redirectUrl
     * @param \Magento\Sales\Model\Order|null $order
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function createSuccessResponse($message, $redirectUrl, $order = null)
    {
        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'message' => $message,
            'redirect' => $this->_url->getUrl($redirectUrl),
        ]);
    }

    /**
     * Creates an error response with a redirect.
     *
     * @param string $message
     * @param string $redirectPath
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function createErrorResponse($message, $redirectPath)
    {
        $this->messageManager->addError($message);

        return $this->resultJsonFactory->create()->setData([
            'success' => false,
            'message' => $message,
            'redirect' => $this->_url->getUrl($redirectPath),
        ]);
    }

    /**
     * Add Base Request Params function
     *
     * @param array $postData
     * @return array
     */
    protected function baseRequestParams($postData)
    {
        $edition = $this->productMetadata->getEdition();
        $version = $this->productMetadata->getVersion();
        $ipAddress = $this->helper->getIpAddress();
        $storeId = $this->getStoreIdFromQuote();
        $newParams = [
            'xVersion' => '5.0.0',
            'xSoftwareName' => 'Magento ' . $edition . " ". $version,
            'xSoftwareVersion' => '1.2.72',
            'xAllowDuplicate' => 1,
            'xKey' => $this->config->getValue(
                'cardknox_transaction_key',
                $storeId
            ),
        ];

        // Merge arrays, giving precedence to $newParams
        $requestParams = array_merge($postData, $newParams);
        return $requestParams;
    }

    /**
     * Get Store ID from quote
     *
     * @return int
     */
    protected function getStoreIdFromQuote()
    {
        if ($this->_checkoutSession->getQuote()) {
            return $this->_checkoutSession->getQuote()->getStore()->getId();
        }
        return 1;
    }
}
