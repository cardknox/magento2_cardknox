<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
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
    const xCardNum = 'xCardNum';
    const xCVV = 'xCVV';
    const cc_exp_month = 'cc_exp_month';
    const cc_exp_year = 'cc_exp_year';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::xCardNum,
        self::xCVV,
        self::cc_exp_month,
        self::cc_exp_year,
        "is_active_payment_token_enabler"
    ];

    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }
        // $additionalData = new DataObject($additionalData);
        // $paymentMethod = $this->readMethodArgument($observer);

        // $payment = $observer->getPaymentModel();
        // if (!$payment instanceof InfoInterface) {
        //     $payment = $paymentMethod->getInfoInstance();
        // }

        // if (!$payment instanceof InfoInterface) {
        //     throw new LocalizedException(__('Payment model does not provided.'));
        // }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }

        // $paymentInfo->setAdditionalInformation("xToken", $additionalData->getData("xToken"));
        // $paymentInfo->setCcLast4(substr($additionalData->getData('cc_number'), -4));
        // $paymentInfo->setCcType($additionalData->getData('cc_type'));
        // $paymentInfo->setCcExpMonth($additionalData->getData('cc_exp_month'));
        // $paymentInfo->setCcExpYear($additionalData->getData('cc_exp_year'));
        // $paymentInfo->setAdditionalInformation("xCardNum", $additionalData->getData('xCardNum'));
    }
}
