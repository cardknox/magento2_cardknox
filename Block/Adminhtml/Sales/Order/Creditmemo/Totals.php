<?php

namespace CardknoxDevelopment\Cardknox\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template;

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
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
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
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getCkgiftcardAmount()) {
            return $this;
        }

        if ($this->getSource()->getCkgiftcardAmount()) {
            $total = new \Magento\Framework\DataObject(
                [
                    'code' => 'ckgiftcardamount',
                    'strong' => false,
                    'value' => -$this->getSource()->getCkgiftcardAmount(),
                    'label' => "Cardknox Giftcard Amount",
                ]
            );

            $this->getParentBlock()->addTotalBefore($total, 'grand_total');
            $creditmemoGrandTotal = $this->getCreditmemo()->getGrandTotal() - $this->getSource()->getCkgiftcardAmount();
            $this->getCreditmemo()->setGrandTotal($creditmemoGrandTotal);
        }
        return $this;
    }
}
