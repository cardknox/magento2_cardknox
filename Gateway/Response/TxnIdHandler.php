<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;

class TxnIdHandler implements HandlerInterface
{
    public const xRefNum = 'xRefNum';
    public const xMaskedCardNumber = 'xMaskedCardNumber';
    public const xAvsResult = 'xAvsResult';
    public const xCvvResult = 'xCvvResult';
    public const xCardType = 'xCardType';
    public const xToken = 'xToken';
    public const xAuthCode = 'xAuthCode';
    public const xBatch = 'xBatch';
    public const xAuthAmount = 'xAuthAmount';
    public const xStatus = 'xStatus';
    public const xError = 'xError';
    public const xExp = 'xExp';
    public const xCvvResultCode = 'xCvvResultCode';
    public const xAvsResultCode = 'xAvsResultCode';

    /**
     * Config variable
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor function
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * AdditionalInformationMapping variable
     *
     * @var array
     */
    protected $additionalInformationMapping = [
        self::xMaskedCardNumber,
        self::xAvsResult,
        self::xCvvResult,
        self::xCardType,
        self::xExp,
        self::xBatch,
        self::xRefNum,
        self::xAuthCode,
        self::xAvsResultCode,
        self::xCvvResultCode,
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
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[$this::xRefNum]);
        $payment->setIsTransactionClosed(false);
        if ($payment->getLastTransId() == '') {
            foreach ($this->additionalInformationMapping as $item) {
                if (!isset($response[$item])) {
                    continue;
                }
                $payment->setAdditionalInformation($item, $response[$item]);
            }
        } else {
            if (isset($response[self::xBatch])) {
                //batch only gets added after capturing
                $payment->setAdditionalInformation(self::xBatch, $response[self::xBatch]);
            }
        }
    }
    
    /**
     * Get type of credit card mapped from Cardknox
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
//        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCctypesMapper();
        return $mapper[$type];
    }
}
