<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Response;

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

class TxnIdHandler implements HandlerInterface
{
    const xRefNum = 'xRefNum';
    const xMaskedCardNumber = 'xMaskedCardNumber';
    const xAvsResult = 'xAvsResult';
    const xCvvResult = 'xCvvResult';
    const xCardType = 'xCardType';
    const xToken = 'xToken';
    const xAuthCode = 'xAuthCode';
    const xBatch = 'xBatch';
    const xAuthAmount = 'xAuthAmount';
    const xStatus = 'xStatus';
    const xError = 'xError';
    const xExp = 'xExp';
    
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
     * Constructor
     *
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
    }

    protected $additionalInformationMapping = [
        self::xMaskedCardNumber,
        self::xAvsResult,
        self::xCvvResult,
        self::xCardType,
        self::xExp,
        self::xBatch,
        self::xRefNum,
        self::xAuthCode,
        self::xAuthAmount
    ];


    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

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
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[$this::xRefNum]);
        $payment->setCcLast4(substr($response[$this::xMaskedCardNumber], - 4));
        $payment->setCcAvsStatus($response[$this::xAvsResult]);
        $payment->setCcCidStatus($response[$this::xCvvResult]);
        $payment->setCcType($this->getCreditCardType($response[$this::xCardType]));
        $payment->setIsTransactionClosed(false);

        foreach ($this->additionalInformationMapping as $item) {
            if (!isset($response[$item])) {
                continue;
            }
            $payment->setAdditionalInformation($item, $response[$item]);
        }
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
        $token = $response[$this::xToken];
        
        if (empty($token)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($xExp));
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->getCreditCardType($response[$this::xCardType]),
            'maskedCC' => $response[$this::xMaskedCardNumber],
            'expirationDate' => $xExp
        ]));

        return $paymentToken;
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
//		$expDate->add(new \DateInterval('P1M'));
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
//		$replaced = str_replace(' ', '-', strtolower($type));
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
