<?php

namespace CardknoxDevelopment\Cardknox\Block\Sales\Totals;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\RequestInterface;

class CkGiftcard extends Template
{
    protected $order;
    protected $source;

    protected RequestInterface $request;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->request = $request;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get Store
     *
     * @return mixed
     */
    public function getStore()
    {
        return $this->order->getStore();
    }

    /**
     * Get Order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get Label Properties
     *
     * @return mixed
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get Value Properties
     *
     * @return mixed
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * Init Totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        if ($this->isInvoiceCreation() && $this->areGiftCardValuesEqual()) {
            return $this;
        }

        if ($this->source->getCkgiftcardAmount()) {
            $ckgiftcardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ckgiftcardAmount',
                    'strong' => false,
                    'value' => -$this->source->getCkgiftcardAmount(),
                    'label' => "Cardknox Giftcard Amount",
                ]
            );
            $parent->addTotal($ckgiftcardAmount, 'ckgiftcardAmount');
        }
        return $this;
    }

    /**
     * Check if the current action is 'sales_order_invoice_new'.
     *
     * @return bool
     */
    private function isInvoiceCreation(): bool
    {
        return $this->request->getFullActionName() === 'sales_order_invoice_updateQty';
    }

    /**
     * Compare gift card values between invoice and order.
     *
     * @return bool
     */
    private function areGiftCardValuesEqual(): bool
    {
        $giftCardAmount = round($this->getSource()->getCkgiftcardAmount() ?? 0.00, 2);
        $baseGiftCardInvoiced = round($this->order->getBaseCkgiftCardsInvoiced() ?? 0.00, 2);

        return $giftCardAmount === $baseGiftCardInvoiced;
    }
}
