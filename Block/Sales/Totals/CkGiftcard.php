<?php

namespace CardknoxDevelopment\Cardknox\Block\Sales\Totals;

use Magento\Framework\View\Element\Template;

class CkGiftcard extends Template
{
    protected $_order;
    protected $_source;

    /**
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
     * Check if we nedd display full tax total info
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get Store
     *
     * @return mixed
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * Get Order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->_order;
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
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->_source->getCkgiftcardAmount()) {
            $ckgiftcardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ckgiftcardAmount',
                    'strong' => false,
                    'value' => -$this->_source->getCkgiftcardAmount(),
                    'label' => "Cardknox Giftcard Amount",
                ]
            );
            $parent->addTotal($ckgiftcardAmount, 'ckgiftcardAmount');
        }
        return $this;
    }
}
