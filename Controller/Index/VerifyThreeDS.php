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
     * Undocumented function
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \CardknoxDevelopment\Cardknox\Gateway\Config\Config $config
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \CardknoxDevelopment\Cardknox\Helper\Data $helper
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
        Data $helper
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
            return $resultJson->setData([
                'success' => false,
                'message' => __('No data received in the request.'),
                'redirect' => $this->_url->getUrl('checkout/cart'), // Redirect to cart page
            ]);
        }

        $newPostData = $this->baseRequestParams($postData);
        // Define API endpoint
        $endpoint = self::GETWAY_HOST . '/verify';

        try {
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->post($endpoint, $newPostData);
            // exit;
            // Get response and status code
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            // Parse response if it's in query string format
            parse_str($response, $parsedResponse);
            print_r($parsedResponse);
            exit();

            if (!empty($parsedResponse)) {
                if (isset($parsedResponse['xResult']) && $parsedResponse['xResult'] === 'E') {
                    // $order = $this->orderFactory->create()->loadByIncrementId(000000407);

                    // // print_r($order->getData());
                    // // die;
                    // $order->setEmailSent(emailSent: 0); // Optional: Prevent email from being sent immediately
                    // $this->orderRepository->save($order);

                    // // it's require for redirect order success page
                    // $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                    // $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                    // $this->_checkoutSession->setLastOrderId($order->getEntityId());


                    // if ($order) {
                    //     // it's require for get original order id to order success page
                    //     $this->_checkoutSession->setLastOrderId($order->getId())
                    //                        ->setLastRealOrderId($order->getIncrementId())
                    //                        ->setLastOrderStatus($order->getStatus());
                    // }
                    return $resultJson->setData([
                        'success' => false,
                        'message' => $parsedResponse['xError'] ?? __('An error occurred.'),
                        'redirect' => $this->_url->getUrl('checkout/cart'), // Redirect to cart page
                    ]);
                }

                if (isset($parsedResponse['xResult']) && $parsedResponse['xResult'] === 'A') {
                    return $resultJson->setData([
                        'success' => true,
                        'message' => __('Thank you for your purchase!'),
                        'redirect' => $this->_url->getUrl('checkout/onepage/success'),
                    ]);
                }
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Unexpected response format.'),
                'redirect' => $this->_url->getUrl('checkout/cart'), // Redirect to cart page
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Error: %1', $e->getMessage()),
                'redirect' => $this->_url->getUrl('checkout/cart'), // Redirect to cart page
            ]);
        }
    }

    /**
     * Return JSON response.
     *
     * @param array $response
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function jsonResponse(array $response)
    {
        return $this->resultJsonFactory->create()->setData($response);
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
        $storeId = 1;
        $newParams = [
            'xVersion' => '5.0.0',
            'xSoftwareName' => 'Magento ' . $edition . " ". $version,
            'xSoftwareVersion' => '1.2.69',
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
}
