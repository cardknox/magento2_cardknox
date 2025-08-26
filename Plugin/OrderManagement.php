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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * __construct function
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->logger = $logger;
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
        try {
            $quoteId = $order->getQuoteId();
            $storeId = $order->getStoreId();

            if (!$quoteId) {
                return [$order];
            }

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->getQuote($storeId, $quoteId);

            if (!$quote || !$quote->getId()) {
                return [$order];
            }

            $paymentQuote = $quote->getPayment();
            if (!$paymentQuote) {
                return [$order];
            }

            // Get payment method code directly from payment object instead of method instance
            $method = $paymentQuote->getMethod();

            // If method is not set, try to get from method instance safely
            if (!$method) {
                try {
                    $methodInstance = $paymentQuote->getMethodInstance();
                    if ($methodInstance) {
                        $method = $methodInstance->getCode();
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Cardknox OrderManagement beforePlace getMethodInstance Exception: ' . $e->getMessage(), [
                        'exception' => $e,
                        'quote_id' => $quoteId
                    ]);
                    // Skip plugin processing if payment method is not available
                    return [$order];
                }
            }

            if (!$method || !in_array($method, ["cardknox_google_pay", "cardknox_apple_pay"])) {
                return [$order];
            }

            $firstName = $paymentQuote->getAdditionalInformation('shippingAddressFirstname');

            // Handle address modifications for supported payment methods
            if ($firstName !== null) {
                $shippingAddress = $quote->getShippingAddress();
                $billingAddress = $quote->getBillingAddress();

                if (!$quote->getIsVirtual() && $shippingAddress) {
                    $this->modifyShippingAddress($shippingAddress, $firstName);
                } elseif ($billingAddress) {
                    $this->modifyBillingAddress($billingAddress, $firstName);
                }
            }

            // Update quote addresses and telephone for supported methods
            $shippingAddress = $quote->getShippingAddress();
            $billingAddress = $quote->getBillingAddress();

            $this->updateQuoteAddress($shippingAddress, $billingAddress);
            $this->updateTelephone($shippingAddress, $billingAddress);

        } catch (\Exception $e) {
            $this->logger->critical('Cardknox OrderManagement beforePlace Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'quote_id' => $order->getQuoteId(),
                'store_id' => $order->getStoreId()
            ]);
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
        try {
            $orderId = $result->getIncrementId();

            if (!$orderId) {
                return $result;
            }

            $payment = $result->getPayment();
            if (!$payment) {
                return $result;
            }

            // Get payment method code directly from payment object instead of method instance
            $method = $payment->getMethod();

            // If method is not set, try to get from method instance safely
            if (!$method) {
                try {
                    $methodInstance = $payment->getMethodInstance();
                    if ($methodInstance) {
                        $method = $methodInstance->getCode();
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Cardknox OrderManagement afterPlace getMethodInstance Exception: ' . $e->getMessage(), [
                        'exception' => $e,
                        'order_id' => $result->getIncrementId()
                    ]);
                    return $result; // Skip plugin processing if payment method is not available
                }
            }

            if (!$method || !in_array($method, ["cardknox_google_pay", "cardknox_apple_pay"])) {
                return $result;
            }

            $firstName = $payment->getAdditionalInformation('shippingAddressFirstname');

            // Handle address modifications for supported payment methods
            if ($firstName !== null) {
                $shippingAddress = $result->getShippingAddress();
                $billingAddress = $result->getBillingAddress();

                if (!$result->getIsVirtual() && $shippingAddress) {
                    $this->modifyShippingAddress($shippingAddress, $firstName);
                } elseif ($billingAddress) {
                    $this->modifyBillingAddress($billingAddress, $firstName);
                }
            }

            // Update addresses and telephone for supported methods
            $shippingAddress = $result->getShippingAddress();
            $billingAddress = $result->getBillingAddress();

            $this->updateQuoteAddress($shippingAddress, $billingAddress);
            $this->updateTelephone($shippingAddress, $billingAddress);

        } catch (\Exception $e) {
            $this->logger->critical('Cardknox OrderManagement afterPlace Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id' => $result->getIncrementId(),
                'entity_id' => $result->getEntityId()
            ]);
        }

        return $result;
    }

    /**
     * GetQuote function
     *
     * @param int $storeId
     * @param int $quoteId
     * @return \Magento\Quote\Model\Quote|null
     */
    public function getQuote($storeId, $quoteId)
    {
        try {
            return $this->quoteFactory->create()->setStoreId($storeId)->load($quoteId);
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement getQuote Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'quote_id' => $quoteId,
                'store_id' => $storeId
            ]);
            return null;
        }
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
        try {
            if (!$shippingAddress) {
                return;
            }

            if (!empty($shippingAddressFirstname) && !$shippingAddress->getFirstname()) {
                $shippingAddress->setFirstname($shippingAddressFirstname);
                $shippingAddress->save();
            }
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement modifyShippingAddress Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'firstname' => $shippingAddressFirstname
            ]);
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
        try {
            if (!$billingAddress) {
                return;
            }

            if (!empty($billingAddressFirstname) && !$billingAddress->getFirstname()) {
                $billingAddress->setFirstname($billingAddressFirstname);
                $billingAddress->save();
            }
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement modifyBillingAddress Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'firstname' => $billingAddressFirstname
            ]);
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
        try {
            // Update firstname & lastname of shipping address
            if ($shippingAddress &&
                !empty($shippingAddress->getFirstname()) &&
                empty($shippingAddress->getLastname())) {
                $this->updateShippingAddressNameData($shippingAddress, $shippingAddress->getFirstname());
            }

            // Update firstname & lastname of billing address
            if ($billingAddress &&
                !empty($billingAddress->getFirstname()) &&
                empty($billingAddress->getLastname())) {
                $this->updateBillingAddressNameData($billingAddress, $billingAddress->getFirstname());
            }
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement updateQuoteAddress Exception: ' . $e->getMessage(), [
                'exception' => $e
            ]);
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
        try {
            if (!$shippingAddress || empty($name)) {
                return;
            }

            // @codingStandardsIgnoreStart
            $nameArray = explode(" ", trim($name), 3);

            if (count($nameArray) >= 1) {
                $shippingAddress->setFirstname($nameArray[0]);
            }
            if (count($nameArray) == 2) {
                $shippingAddress->setLastname($nameArray[1]);
            } elseif (count($nameArray) >= 3) {
                $shippingAddress->setMiddlename($nameArray[1]);
                $shippingAddress->setLastname($nameArray[2]);
            }

            $shippingAddress->setSaveInAddressBook(false);
            $shippingAddress->setSameAsBilling(false);
            $shippingAddress->save();
            // @codingStandardsIgnoreEnd
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement updateShippingAddressNameData Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'name' => $name
            ]);
        }
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
        try {
            if (!$billingAddress || empty($name)) {
                return;
            }

            // @codingStandardsIgnoreStart
            $nameArray = explode(" ", trim($name), 3);

            if (count($nameArray) >= 1) {
                $billingAddress->setFirstname($nameArray[0]);
            }
            if (count($nameArray) == 2) {
                $billingAddress->setLastname($nameArray[1]);
            } elseif (count($nameArray) >= 3) {
                $billingAddress->setMiddlename($nameArray[1]);
                $billingAddress->setLastname($nameArray[2]);
            }

            $billingAddress->setSaveInAddressBook(false);
            $billingAddress->save();
            // @codingStandardsIgnoreEnd
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement updateBillingAddressNameData Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'name' => $name
            ]);
        }
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
        try {
            if (!$shippingAddress || !$billingAddress) {
                return;
            }

            $telephone = $billingAddress->getTelephone();

            if (!empty($shippingAddress) &&
                empty($shippingAddress->getTelephone()) &&
                !empty($telephone)) {

                // If telephone has country code with +, extract the number part
                if (strpos($telephone, '+') === 0 && strpos($telephone, ' ') !== false) {
                    // Extract everything after the first space (remove country code)
                    $cleanTelephone = trim(substr($telephone, strpos($telephone, ' ') + 1));
                } else {
                    $cleanTelephone = $telephone;
                }

                if (!empty($cleanTelephone)) {
                    $shippingAddress->setTelephone($cleanTelephone);
                    $shippingAddress->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Cardknox OrderManagement updateTelephone Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'telephone' => $billingAddress ? $billingAddress->getTelephone() : 'N/A'
            ]);
        }
    }
}
