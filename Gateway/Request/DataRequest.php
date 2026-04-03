<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Helper\Data;

class DataRequest implements BuilderInterface
{
    public const AMOUNT = 'xAmount';
    public const INVOICE = 'xInvoice';
    public const CARDNUM = 'xCardNum';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Constructor
     *
     * @param Config $config
     * @param Data $helper
     */
    public function __construct(
        Config $config,
        Data $helper
    ) {
        $this->config = $config;
        $this->helper = $helper;
    }

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

        $level3Data = $this->getLevel3Data($paymentDO);

        //its a reguler capture
        if ($payment->getLastTransId() != '') {
            return $level3Data;
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
            'xEmail' => $billing->getEmail(),
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
            ];
        } else {
            $result2 = [];
        }

        return array_merge_recursive($result, $result2, $level3Data);
    }

    /**
     * Get Level 3 data (order-level and line-item fields)
     *
     * @param PaymentDataObjectInterface $paymentDO
     * @return array
     */
    private function getLevel3Data(PaymentDataObjectInterface $paymentDO): array
    {
        if (!$this->config->isLevel3Enabled()) {
            return [];
        }

        /** @var \Magento\Sales\Model\Order $salesOrder */
        $salesOrder = $paymentDO->getPayment()->getOrder();

        // Order-level fields
        $result = [
            'xPONum'      => $salesOrder->getIncrementId(),
            'xTax'        => $this->helper->formatPrice($salesOrder->getTaxAmount()),
            'xDiscount'   => $this->helper->formatPrice(abs((float) $salesOrder->getDiscountAmount())),
            'xShipAmount' => $this->helper->formatPrice($salesOrder->getShippingAmount()),
        ];

        // Ship-from ZIP — only when MSI is NOT enabled
        if (!$this->helper->isMsiEnabled()) {
            $shipFromZip = $this->helper->getShippingOriginZip();
            if ($shipFromZip) {
                $result['xShipFromZip'] = $shipFromZip;
            }
        }

        // Line-item fields
        $items = $salesOrder->getAllItems();
        $index = 1;

        foreach ($items as $item) {
            // Skip parent items — only pass child items
            if ($item->getChildrenItems()) {
                continue;
            }

            $qty = (int) $item->getQtyOrdered();

            // For child items with price=0, get price from parent
            $price = $item->getPrice();
            if ($price == 0 && $item->getParentItem()) {
                $price = $item->getParentItem()->getPrice();
            }

            $result['x' . $index . 'Sku']         = (string) $item->getSku();
            $result['x' . $index . 'Description'] = (string) $item->getName();
            $result['x' . $index . 'Qty']         = (string) $qty;
            $result['x' . $index . 'UnitPrice']   = $this->helper->formatPrice($price);

            $index++;
        }

        return $result;
    }
}
