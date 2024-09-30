<?php
namespace CardknoxDevelopment\Cardknox\Block\Sales\Totals\CkGiftcard;

class Order extends \Magento\Framework\View\Element\Template
{
    protected $_order;
    protected $_source;

    /**
     * __construct function
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
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

    public function getStore()
    {
        return $this->_order->getStore();
    }

    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @return array
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return array
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        
        if (!$this->_order->getCkgiftcardAmount()) {
            return $this;
        }
        
        if ($this->_order->getCkgiftcardAmount()) {
            $ckgiftcardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ckgiftcardAmount',
                    'strong' => false,
                    'value' => -$this->_order->getCkgiftcardAmount(),
                    'label' => "Cardknox Giftcard Amount",
                ]
            );

            $parent->addTotal($ckgiftcardAmount, 'ckgiftcardAmount');
        }

        return $this;
    }

}
