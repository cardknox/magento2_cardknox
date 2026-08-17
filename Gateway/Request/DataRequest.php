<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Helper\Data;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Invoice;

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
            throw new InvalidArgumentException('Payment data object should be provided');
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
            'xBillCountry' => $billing->getCountryId(),
            'xBillPhone' => $billing->getTelephone(),
            'xEmail' => $billing->getEmail(),
        ];

        if ($shipping != "") {
            $result2 = [
                'xShipFirstName' => $shipping->getFirstname(),
                'xShipLastName' => $shipping->getLastname(),
                'xShipCompany' => $shipping->getCompany(),
                'xShipStreet' => $shipping->getStreetLine1(),
                'xShipStreet2' => $shipping->getStreetLine2(),
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

        $payment = $paymentDO->getPayment();
        /** @var \Magento\Sales\Model\Order $salesOrder */
        $salesOrder = $payment->getOrder();

        // Check if this is a capture (split capture / regular capture)
        $isCapture = ($payment->getLastTransId() != '');
        $invoice = null;

        if ($isCapture) {
            $invoice = $this->getCurrentInvoice($salesOrder);

            // Drop Level 3 when this invoice does not carry the order's tax, shipping,
            // discount and gift card. On a capture the gateway ignores the order-level
            // amounts in the request and re-adds the ones held on the authorization, so it
            // reconciles line items + authorization tax + authorization shipping −
            // authorization discount against this capture's amount. That balances only for
            // an invoice carrying the order's full set of them — which Magento puts on the
            // first invoice, leaving later ones with nothing but their line items. Sending
            // those amounts as zero or leaving them out makes no difference; both were
            // rejected. The authorization already carried the full Level 3 record.
            //
            // Deliberately keyed on the totals rather than on the split capture setting:
            // Apple Pay has no such setting yet still reaches this path through a partially
            // invoiced cc:capture, as does a credit card order with split capture switched
            // off. An order with no tax, shipping, discount or gift card matches on every
            // invoice, so its partial captures all keep their Level 3 line items.
            if ($invoice && $this->hasOrderLevelMismatch($salesOrder, $invoice)) {
                return [];
            }
        }

        // Order-level fields — use invoice totals for capture, order totals for auth/sale
        // The gateway validates line items + tax + shipping − discount against the amount. A
        // Cardknox gift card is settled outside the card transaction and already lowered that
        // amount, so it is folded into xDiscount on top of the Magento core discount.
        if ($invoice) {
            $discount = abs((float) $invoice->getDiscountAmount())
                + $this->getGiftCardAmount($invoice);
            $result = [
                'xPONum'      => $salesOrder->getIncrementId(),
                'xTax'        => $this->helper->formatPrice($invoice->getTaxAmount()),
                'xDiscount'   => $this->helper->formatPrice($discount),
                'xShipAmount' => $this->helper->formatPrice($invoice->getShippingAmount()),
            ];
            $lineData = $this->getInvoiceLineItems($invoice);
        } else {
            $discount = abs((float) $salesOrder->getDiscountAmount())
                + $this->getGiftCardAmount($salesOrder);
            $result = [
                'xPONum'      => $salesOrder->getIncrementId(),
                'xTax'        => $this->helper->formatPrice($salesOrder->getTaxAmount()),
                'xDiscount'   => $this->helper->formatPrice($discount),
                'xShipAmount' => $this->helper->formatPrice($salesOrder->getShippingAmount()),
            ];
            $lineData = $this->getOrderLineItems($salesOrder);
        }

        // Ship-from ZIP — only when MSI is NOT enabled
        if (!$this->helper->isMsiEnabled()) {
            $shipFromZip = $this->helper->getShippingOriginZip();
            if ($shipFromZip) {
                $result['xShipFromZip'] = $shipFromZip;
            }
        }

        // shipping tax is intentionally excluded from this signal — kept independent from xTax
        $result['xNonTaxable'] = $lineData['anyLineHasTax'] ? 'false' : 'true';

        return array_merge($result, $lineData['lineItems']);
    }

    /**
     * Get the Cardknox gift card amount applied to the given order or invoice
     *
     * Returns 0.0 when the gift card feature is disabled or no gift card was applied.
     * For an invoice this is the gift card portion consumed by that invoice only, so
     * split captures each report their own share.
     *
     * @param \Magento\Sales\Model\Order|\Magento\Sales\Model\Order\Invoice $entity
     * @return float
     */
    private function getGiftCardAmount($entity): float
    {
        if (!$this->helper->isCardknoxGiftcardEnabled()) {
            return 0.0;
        }

        return abs((float) $entity->getCkgiftcardAmount());
    }

    /**
     * Check whether the invoice carries the order's tax, shipping, discount and gift card
     *
     * The line items sent with a capture describe just that invoice, but the gateway
     * reconciles them against the order-level amounts held on the authorization. The two
     * sides agree only when this invoice carries the order's full tax, shipping, discount
     * and gift card.
     *
     * @param \Magento\Sales\Model\Order $salesOrder
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return bool
     */
    private function hasOrderLevelMismatch($salesOrder, $invoice): bool
    {
        $difference = $this->getOrderLevelAdjustment($invoice)
            - $this->getOrderLevelAdjustment($salesOrder);

        return abs($difference) > 0.009;
    }

    /**
     * Get the amount an order or invoice adds on top of its line items
     *
     * Mirrors what the gateway adds to the line item total: tax plus shipping less the
     * discount, gift card included exactly as xDiscount reports it.
     *
     * @param \Magento\Sales\Model\Order|\Magento\Sales\Model\Order\Invoice $entity
     * @return float
     */
    private function getOrderLevelAdjustment($entity): float
    {
        return (float) $entity->getTaxAmount()
            + (float) $entity->getShippingAmount()
            - abs((float) $entity->getDiscountAmount())
            - $this->getGiftCardAmount($entity);
    }

    /**
     * Get line items from invoice (for capture)
     *
     * Returns array{lineItems: array<string,string>, anyLineHasTax: bool}.
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return array
     */
    private function getInvoiceLineItems($invoice): array
    {
        $lineItems = [];
        $anyLineHasTax = false;
        $index = 1;

        foreach ($invoice->getAllItems() as $invoiceItem) {
            $qty = (int) $invoiceItem->getQty();

            // Skip items not being invoiced
            if ($qty <= 0) {
                continue;
            }

            $orderItem = $invoiceItem->getOrderItem();

            // Skip child items — parent holds the correct price
            if ($orderItem->getParentItem()) {
                continue;
            }

            $price = $orderItem->getPrice();

            $lineItems['x' . $index . 'Sku']         = (string) $invoiceItem->getSku();
            $lineItems['x' . $index . 'Description'] = (string) $invoiceItem->getName();
            $lineItems['x' . $index . 'Qty']         = (string) $qty;
            $lineItems['x' . $index . 'UnitPrice']   = $this->helper->formatPrice($price);
            $lineItems['x' . $index . 'TaxRate']     = $this->helper->formatPrice($orderItem->getTaxPercent() ?? 0);
            $lineItems['x' . $index . 'TaxAmount']   = $this->helper->formatPrice($invoiceItem->getTaxAmount() ?? 0);

            if ((float) $invoiceItem->getTaxAmount() > 0) {
                $anyLineHasTax = true;
            }

            $index++;
        }

        return [
            'lineItems'     => $lineItems,
            'anyLineHasTax' => $anyLineHasTax,
        ];
    }

    /**
     * Get line items from order (for authorize/sale)
     *
     * Returns array{lineItems: array<string,string>, anyLineHasTax: bool}.
     *
     * @param \Magento\Sales\Model\Order $salesOrder
     * @return array
     */
    private function getOrderLineItems($salesOrder): array
    {
        $lineItems = [];
        $anyLineHasTax = false;
        $index = 1;

        foreach ($salesOrder->getAllItems() as $item) {
            // Skip child items — parent holds the correct price
            if ($item->getParentItem()) {
                continue;
            }

            $qty = (int) $item->getQtyOrdered();
            $price = $item->getPrice();

            $lineItems['x' . $index . 'Sku']         = (string) $item->getSku();
            $lineItems['x' . $index . 'Description'] = (string) $item->getName();
            $lineItems['x' . $index . 'Qty']         = (string) $qty;
            $lineItems['x' . $index . 'UnitPrice']   = $this->helper->formatPrice($price);
            $lineItems['x' . $index . 'TaxRate']     = $this->helper->formatPrice($item->getTaxPercent() ?? 0);
            $lineItems['x' . $index . 'TaxAmount']   = $this->helper->formatPrice($item->getTaxAmount() ?? 0);

            if ((float) $item->getTaxAmount() > 0) {
                $anyLineHasTax = true;
            }

            $index++;
        }

        return [
            'lineItems'    => $lineItems,
            'anyLineHasTax' => $anyLineHasTax,
        ];
    }

    /**
     * Get the current invoice being captured
     *
     * Returns the latest unpaid invoice from the order
     *
     * @param \Magento\Sales\Model\Order $salesOrder
     * @return \Magento\Sales\Model\Order\Invoice|null
     */
    private function getCurrentInvoice($salesOrder)
    {
        $invoice = null;
        foreach ($salesOrder->getInvoiceCollection() as $inv) {
            if ($inv->getState() == Invoice::STATE_OPEN
                || !$inv->getEntityId()
            ) {
                $invoice = $inv;
            }
        }

        return $invoice;
    }
}
