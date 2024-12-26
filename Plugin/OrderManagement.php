<?php
namespace CardknoxDevelopment\Cardknox\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

class OrderManagement
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * __construct function
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * OrderManagement
     *
     * @param OrderManagementInterface $subject
     * @param OrderInterface           $order
     *
     * @return OrderInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ): array {
        $quoteId = $order->getQuoteId();
        $storeId = $order->getStoreId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getQuote($storeId, $quoteId);

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $paymentQuote = $quote->getPayment();
        $method = $paymentQuote->getMethodInstance()->getCode();

        $firstName = $paymentQuote->getAdditionalInformation('shippingAddressFirstname');
        if ($quoteId && $firstName !== null &&
            ($method == "cardknox_google_pay" || $method == "cardknox_apple_pay")) {
            if (!$quote->getIsVirtual()) {
                $this->modifyShippingAddress($shippingAddress, $firstName);
            } else {
                $this->modifyBillingAddress($billingAddress, $firstName);
            }
        }

        if ($quoteId && $method == "cardknox_google_pay" || $method == "cardknox_apple_pay") {
            $this->updateQuoteAddress($shippingAddress, $billingAddress);
            $this->updateTelephone($shippingAddress, $billingAddress);
        }
        return [$order];
    }

    /**
     * _afterPlace function
     *
     * @param OrderManagementInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $result
    ) {
        $orderId = $result->getIncrementId();
        if ($orderId) {
            $shippingAddress = $result->getShippingAddress();
            $billingAddress = $result->getBillingAddress();
            $payment = $result->getPayment();
            $method = $payment->getMethodInstance()->getCode();

            $firstName = $payment->getAdditionalInformation('shippingAddressFirstname');
            if ($firstName !== null &&
                ($method == "cardknox_google_pay" || $method == "cardknox_apple_pay")) {
                if (!$result->getIsVirtual()) {
                    $this->modifyShippingAddress($shippingAddress, $firstName);
                } else {
                    $this->modifyBillingAddress($billingAddress, $firstName);
                }
            }

            if ($method == "cardknox_google_pay" || $method == "cardknox_apple_pay") {
                $this->updateQuoteAddress($shippingAddress, $billingAddress);
                $this->updateTelephone($shippingAddress, $billingAddress);
            }
        }
        return $result;
    }

    /**
     * GetQuote function
     *
     * @param int $storeId
     * @param int $quoteId
     * @return mixed
     */
    public function getQuote($storeId, $quoteId)
    {
        return $this->quoteFactory->create()->setStoreId($storeId)->load($quoteId);
    }
    /**
     * _modifyShippingAddress function
     *
     * @param mixed $shippingAddress
     * @param mixed $shippingAddressFirstname
     * @return void
     */
    protected function modifyShippingAddress($shippingAddress, $shippingAddressFirstname)
    {
        if (!empty($shippingAddressFirstname) &&
            !$shippingAddress->getFirstname() &&
            !empty($shippingAddressFirstname)
        ) {
            $shippingAddress->setFirstname($shippingAddressFirstname);
            $shippingAddress->save();
        }
    }

    /**
     * _modifyBillingAddress function
     *
     * @param mixed $billingAddress
     * @param mixed $billingAddressFirstname
     * @return void
     */
    protected function modifyBillingAddress($billingAddress, $billingAddressFirstname)
    {
        if (!empty($billingAddressFirstname) &&
            !$billingAddress->getFirstname() &&
            !empty($billingAddressFirstname)
        ) {
            $billingAddress->setFirstname($billingAddressFirstname);
            $billingAddress->save();
        }
    }

    /**
     * _updateQuoteAddress function
     *
     * @param mixed $shippingAddress
     * @param mixed $billingAddress
     * @return void
     */
    protected function updateQuoteAddress($shippingAddress, $billingAddress)
    {
        if (!empty($shippingAddress) &&
            !empty($shippingAddress->getFirstname()) &&
            empty($shippingAddress->getLastname())
        ) {
            $this->updateShippingAddressNameData($shippingAddress, $shippingAddress->getFirstname());
        }
        if (!empty($billingAddress) &&
            !empty($billingAddress->getFirstname()) &&
            empty($billingAddress->getLastname())
        ) {
            $this->updateBillingAddressNameData($billingAddress, $billingAddress->getFirstname());
        }
    }
    /**
     * Update shipping address name function
     *
     * @param mixed $shippingAddress
     * @param string $name
     * @return void
     */
    protected function updateShippingAddressNameData($shippingAddress, $name)
    {
        // @codingStandardsIgnoreStart
        $nameArray = explode(" ", $name, 3);

        $shippingAddress->setFirstname($nameArray[0]);
        if (sizeof($nameArray) == 2) {
            $shippingAddress->setLastname($nameArray[1]);
        } elseif (sizeof($nameArray) == 3) {
            $shippingAddress->setMiddlename($nameArray[1]);
            $shippingAddress->setLastname($nameArray[2]);
        }
        $shippingAddress->setSaveInAddressBook(false);
        $shippingAddress->setSameAsBilling(false);
        $shippingAddress->save();
        // @codingStandardsIgnoreEnd
    }
    /**
     * Update billing address name function
     *
     * @param mixed $billingAddress
     * @param string $name
     * @return void
     */
    protected function updateBillingAddressNameData($billingAddress, $name)
    {
        // @codingStandardsIgnoreStart
        $nameArray = explode(" ", $name, 3);
        $billingAddress->setFirstname($nameArray[0]);
        if (sizeof($nameArray) == 2) {
            $billingAddress->setLastname($nameArray[1]);
        } elseif (sizeof($nameArray) == 3) {
            $billingAddress->setMiddlename($nameArray[1]);
            $billingAddress->setLastname($nameArray[2]);
        }
        $billingAddress->setSaveInAddressBook(false);
        $billingAddress->save();
        // @codingStandardsIgnoreEnd
    }
    /**
     * _updateTelephone function
     *
     * @param mixed $shippingAddress
     * @param mixed $billingAddress
     * @return void
     */
    protected function updateTelephone($shippingAddress, $billingAddress)
    {
        $telephone = $billingAddress->getTelephone();
        if (!empty($shippingAddress) &&
            empty($shippingAddress->getTelephone())
        ) {
            if (strpos($telephone, '+') !== false && !empty($telephone)) {
                $telephone = strstr($telephone, ' ');
                $shippingAddress->setTelephone($telephone);
                $shippingAddress->save();
            }
        }
    }
}
