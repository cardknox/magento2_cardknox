<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Response;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
//the below is needed since version 2.1.3
//use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Payment\Model\InfoInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Payment\Model\Method\Logger;

class VaultHandler implements HandlerInterface
{
    const X_MASKED_CARD_NUMBER = 'xMaskedCardNumber';
    const xCardType = 'xCardType';
    const xToken = 'xToken';
    const xExp = 'xExp';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var CreditCardTokenFactory
     */
    //    protected $creditCardTokenFactory;

    protected $paymentTokenFactory;
    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    protected $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */

    /**
     * Constructor
     *
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config,
        Logger $logger,
        EncryptorInterface $encryptor
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (
            !isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        if ($payment->getAdditionalInformation("is_active_payment_token_enabler") == "") {
            return;
        }

        $log['VaultHandler save card'] = true;

        $xExp = "";
        if (isset($response[$this::xExp])) {
            $xExp = $response[$this::xExp];
        } elseif ($payment->getAdditionalInformation("cc_exp_month") != "") {
            $xExp = sprintf('%02d%02d', $payment->getAdditionalInformation("cc_exp_month"), substr($payment->getAdditionalInformation("cc_exp_year"), -2));
        }

        // add vault payment token entity to extension attributes
        if ($xExp) {
            $paymentToken = $this->getVaultPaymentToken($response, $xExp);
            if (null !== $paymentToken) {
                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }

        $this->logger->debug($log);
    }

    /**
     * Get vault payment token entity
     *
     * @param  array
     * @return PaymentTokenInterface|null
     */
    private function getVaultPaymentToken(array $response, string $xExp)
    {
        // Check token existing in gateway response
        if (isset($response[$this::xToken])) {
            $token = $response[$this::xToken];
            if (empty($token)) {
                return null;
            }
        } else {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($xExp));
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->getCreditCardType($response[$this::xCardType]),
            'maskedCC' => $response[$this::X_MASKED_CARD_NUMBER],
            'expirationDate' => $xExp
        ]));

        return $paymentToken;
    }

    /**
     * Generate vault payment public hash
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    public function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param string $xExp
     * @return string
     */
    private function getExpirationDate(string $xExp)
    {
        $expDate = new \DateTime(
            '20' . substr($xExp, -2)
                . '-'
                . substr($xExp, 0, 2)
                . '-'
                . '01'
                . ' '
                . '00:00:00',
            new \DateTimeZone('UTC')
        );
        return $expDate->format('Y-m-d 00:00:00');
    }
    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     * Get type of credit card mapped from Cardknox
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $mapper = $this->config->getCctypesMapper();
        return $mapper[$type];
    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
