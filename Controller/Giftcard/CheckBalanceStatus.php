<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use CardknoxDevelopment\Cardknox\Helper\Data as Helper;

class CheckBalanceStatus extends Action
{
    /**
     *
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     *
     * @var \CardknoxDevelopment\Cardknox\Helper\Giftcard
     */
    protected $_giftcardHelper;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * __construct function
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     * @param Giftcard $giftcardHelper
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        Giftcard $giftcardHelper,
        Helper $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_curl = $curl;
        $this->_giftcardHelper = $giftcardHelper;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $isCardknoxGiftcardEnabled = $this->helper->isCardknoxGiftcardEnabled();
        if (!$isCardknoxGiftcardEnabled) {
            return $result->setData([
                'success' => false,
                'message' => __('Please enable Cardknox GiftCard.'),
            ]);
        }
        $giftCardCode = $this->getRequest()->getParam('giftcard_code');
        if (!$giftCardCode) {
            return $result->setData([
                'success' => false,
                'message' => __('Gift Card code is required.'),
            ]);
        }
        try {
            $apiResponse = $this->_giftcardHelper->checkGiftCardBalanceStatus($giftCardCode);

            if ($apiResponse['xStatus'] == "Approved") {
                $xRemainingBalance = $apiResponse['xRemainingBalance'];
                $xActivationStatus = $apiResponse['xActivationStatus'];
                $apiResponseData = [
                    "xRemainingBalance" => $xRemainingBalance,
                    "xActivationStatus" => $xActivationStatus
                ];
                $xRemainingBalanceWithCurrency = $this->_giftcardHelper->getFormattedAmount($xRemainingBalance);
                $message = 'Giftcard status is '.$xActivationStatus.'. Your giftcard remaining balance is '.$xRemainingBalanceWithCurrency;
                return $result->setData([
                    'success' => true,
                    'message' => $message,
                    'data' => $apiResponseData
                ]);
            } elseif ($apiResponse['xStatus'] == "Error") {
                return $result->setData([
                    'success' => false,
                    'message' => $apiResponse['xError'],
                ]);
            }
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
