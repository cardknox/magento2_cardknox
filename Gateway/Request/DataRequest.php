<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class DataRequest implements BuilderInterface
{
    const AMOUNT = 'xAmount';
    const INVOICE = 'xInvoice';
    const CARDNUM = 'xCardNum';

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        $order = $paymentDO->getOrder();
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        //its a reguler capture
        if ($payment->getLastTransId() != '') {
            return [];
        }
        //its a authorize and capture
        $result = [
            'xBillFirstName' => $billing->getFirstname(),
            'xBillLastName' => $billing->getLastname(),
            'xBillCompany' => $billing->getCompany(),
            'xBillStreet' => $billing->getStreetLine1(),
            'xBillStreet2' => $billing->getStreetLine2(),
            'xBillCity' => $billing->getCity(),
            'xBillState' => $billing->getRegionCode(),
            'xBillZip' => $billing->getPostcode(),
            'xBillCountry'=> $billing->getCountryId(),
            'xBillPhone' => $billing->getTelephone(),

        ];
        if ($shipping != "") {
            $result2 = [
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
            $result2 = [];
        }

        return array_merge_recursive($result, $result2);
    }
}
