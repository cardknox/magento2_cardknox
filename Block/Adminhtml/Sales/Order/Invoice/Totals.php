<?php

namespace CardknoxDevelopment\Cardknox\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject;
use Magento\Framework\App\RequestInterface;

class Totals extends Template
{
    protected $_invoice = null;
    protected $_order;
    protected RequestInterface $request;

    public function __construct(
        Template\Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
    }

    /**
     * Get data (totals) source model.
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get the current invoice.
     *
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * Initialize gift card totals in the invoice.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parentBlock = $this->getParentBlock();
        $this->_order = $parentBlock->getOrder();

        if (!$this->hasGiftCardAmount()) {
            return $this;
        }

        if ($this->isInvoiceCreation() && $this->areGiftCardValuesEqual()) {
            return $this;
        }

        $this->addGiftCardTotal();
        $this->updateInvoiceGrandTotal();

        return $this;
    }

    /**
     * Check if the invoice has a gift card amount.
     *
     * @return bool
     */
    private function hasGiftCardAmount(): bool
    {
        return (bool) $this->getSource()->getCkgiftcardAmount();
    }

    /**
     * Check if the current action is 'sales_order_invoice_new'.
     *
     * @return bool
     */
    private function isInvoiceCreation(): bool
    {
        return $this->request->getFullActionName() === 'sales_order_invoice_new';
    }

    /**
     * Compare gift card values between invoice and order.
     *
     * @return bool
     */
    private function areGiftCardValuesEqual(): bool
    {
        $giftCardAmount = round($this->getSource()->getCkgiftcardAmount() ?? 0.00, 2);
        $baseGiftCardInvoiced = round($this->_order->getBaseCkgiftCardsInvoiced() ?? 0.00, 2);

        return $giftCardAmount === $baseGiftCardInvoiced;
    }

    /**
     * Add the gift card amount to the invoice totals.
     */
    private function addGiftCardTotal(): void
    {
        $giftCardAmount = $this->getSource()->getCkgiftcardAmount();

        $total = new DataObject([
            'code'  => 'ckgiftcardamount',
            'value' => -$giftCardAmount,
            'label' => __('Cardknox Giftcard Amount'),
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
    }

    /**
     * Update the grand total of the invoice by subtracting the gift card amount.
     */
    private function updateInvoiceGrandTotal(): void
    {
        $invoice = $this->getInvoice();
        $giftCardAmount = $this->getSource()->getCkgiftcardAmount();

        $newGrandTotal = $invoice->getGrandTotal() - $giftCardAmount;
        $invoice->setGrandTotal($newGrandTotal);
    }
}
