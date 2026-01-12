<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use CardknoxDevelopment\Cardknox\Helper\Data as DataHelper;

class CheckBalanceStatus extends AbstractGiftcardAction
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Giftcard $giftcardHelper
     * @param DataHelper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Giftcard $giftcardHelper,
        DataHelper $helper
    ) {
        parent::__construct($context, $resultJsonFactory, $giftcardHelper, $helper);
    }

    /**
     * Execute controller action to check gift card balance status
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $errorResponse = $this->validateGiftcardEnabled();
        if ($errorResponse) {
            return $errorResponse;
        }

        $giftCardCode = $this->getGiftCardCode();
        $errorResponse = $this->validateGiftCardCode($giftCardCode);
        if ($errorResponse) {
            return $errorResponse;
        }

        try {
            $apiResponse = $this->giftcardHelper->checkGiftCardBalanceStatus($giftCardCode);

            if ($apiResponse['xStatus'] == "Approved") {
                $xRemainingBalance = $apiResponse['xRemainingBalance'];
                $xActivationStatus = $apiResponse['xActivationStatus'];
                $apiResponseData = [
                    "xRemainingBalance" => $xRemainingBalance,
                    "xActivationStatus" => $xActivationStatus
                ];
                $xRemainingBalanceWithCurrency = $this->giftcardHelper->getFormattedAmount($xRemainingBalance);
                $message = 'Giftcard status is ' . $xActivationStatus
                    . '. Your giftcard remaining balance is ' . $xRemainingBalanceWithCurrency;
                return $this->createJsonResponse(true, $message, ['data' => $apiResponseData]);
            } elseif ($apiResponse['xStatus'] == "Error") {
                return $this->createJsonResponse(false, $apiResponse['xError']);
            }
        } catch (LocalizedException $e) {
            return $this->createJsonResponse(false, $e->getMessage());
        }
    }
}
