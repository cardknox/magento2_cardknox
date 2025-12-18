<?php

namespace CardknoxDevelopment\Cardknox\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject;

class Totals extends Template
{
    /**
     * Order Creditmemo
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
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
     * Add the gift card amount to the Creditmemo totals.
     */
    private function addGiftCardTotal(): void
    {
        $giftCardAmount = $this->getSource()->getCkgiftcardAmount();

        $total = new DataObject([
            'code'  => 'ckgiftcardamount',
            'value' => -$giftCardAmount,
            'label' => __('Sola Giftcard Amount'),
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
    }

    /**
     * Update the grand total of the Creditmemo by subtracting the gift card amount.
     */
    private function updateCreditmemoGrandTotal(): void
    {
        $creditmemo = $this->getCreditmemo();
        $giftCardAmount = $this->getSource()->getCkgiftcardAmount();

        $newGrandTotal = $creditmemo->getGrandTotal() - $giftCardAmount;
        $creditmemo->setGrandTotal($newGrandTotal);
    }
}
