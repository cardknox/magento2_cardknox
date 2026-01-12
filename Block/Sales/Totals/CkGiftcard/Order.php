<?php
namespace CardknoxDevelopment\Cardknox\Block\Sales\Totals\CkGiftcard;

use Magento\Framework\View\Element\Template;

class Order extends Template
{
    /**
     * @var \Magento\Sales\Model\Order|null
     */
    protected $_order;

    /**
     * @var \Magento\Framework\DataObject|null
     */
    protected $_source;

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

        if (!$this->_order->getCkgiftcardAmount()) {
            return $this;
        }

        if ($this->_order->getCkgiftcardAmount()) {
            $ckgiftcardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ckgiftcardAmount',
                    'strong' => false,
                    'value' => -$this->_order->getCkgiftcardAmount(),
                    'label' => "Sola Giftcard Amount",
                ]
            );

            $parent->addTotal($ckgiftcardAmount, 'ckgiftcardAmount');
        }

        return $this;
    }
}
