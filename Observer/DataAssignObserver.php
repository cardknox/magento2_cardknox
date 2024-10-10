<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;

use Magento\Payment\Model\InfoInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public const XCARDNUM = 'xCardNum';
    public const XCVV = 'xCVV';
    public const CC_EXP_MONTH = 'cc_exp_month';
    public const CC_EXP_YEAR = 'cc_exp_year';

    /**
     * AdditionalInformationList variable
     *
     * @var array
     */
    protected $additionalInformationList = [
        self::XCARDNUM,
        self::XCVV,
        self::CC_EXP_MONTH,
        self::CC_EXP_YEAR,
        "is_active_payment_token_enabler"
    ];

    /**
     * DataAssignObserver function
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }
        $additionalData = new DataObject($additionalData);
        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
        $paymentInfo->setAdditionalInformation("xAmount", $additionalData->getData('xAmount'));
        $isSplitCapture = $additionalData->getData('isSplitCapture');
        if (isset($isSplitCapture)) {
            $paymentInfo->setAdditionalInformation("isSplitCapture", $additionalData->getData('isSplitCapture'));
        }
        $xPaymentAction = $additionalData->getData('xPaymentAction');
        if (isset($xPaymentAction)) {
            $paymentInfo->setAdditionalInformation("xPaymentAction", $additionalData->getData('xPaymentAction'));
        }
        $isAllowDuplicateTransaction = $additionalData->getData('isAllowDuplicateTransaction');
        if (isset($isAllowDuplicateTransaction)) {
            $paymentInfo->setAdditionalInformation(
                "isAllowDuplicateTransaction",
                $additionalData->getData('isAllowDuplicateTransaction')
            );
        }
        $paymentInfo->setAdditionalInformation(
            "shippingAddressFirstname",
            $additionalData->getData('shipping_address_firstname')
        );
    }
}
