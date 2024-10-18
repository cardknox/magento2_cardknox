<?php

namespace CardknoxDevelopment\Cardknox\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject;
use Magento\Framework\App\RequestInterface;

class Totals extends Template
{
    /**
     * Order invoice
     *
     * @var \Magento\Sales\Model\Order\Creditmemo|null
     */
    protected $_creditmemo = null;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Credit Memo
     *
     * @return mixed
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }
    /**
     * Initialize payment fee totals
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

        if ($this->isCreditmemoCreation() && $this->areGiftCardValuesEqual()) {
            return $this;
        }

        $this->addGiftCardTotal();
        $this->updateCreditmemoGrandTotal();

        return $this;
    }

    /**
     * Check if the creditmemo has a gift card amount.
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
    private function isCreditmemoCreation(): bool
    {
        return $this->request->getFullActionName() === 'sales_order_creditmemo_new' || $this->request->getFullActionName() === 'sales_order_creditmemo_view';
    }

    /**
     * Compare gift card values between invoice and order.
     *
     * @return bool
     */
    private function areGiftCardValuesEqual(): bool
    {
        $giftCardAmount = round($this->getSource()->getCkgiftcardAmount() ?? 0.00, 2);
        $baseGiftCardCreditmemo = round($this->_order->getBaseCkgiftCardsRefunded() ?? 0.00, 2);

        return $giftCardAmount === $baseGiftCardCreditmemo;
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
    private function updateCreditmemoGrandTotal(): void
    {
        $creditmemo = $this->getCreditmemo();
        $giftCardAmount = $this->getSource()->getCkgiftcardAmount();

        $newGrandTotal = $creditmemo->getGrandTotal() - $giftCardAmount;
        $creditmemo->setGrandTotal($newGrandTotal);
    }
}
