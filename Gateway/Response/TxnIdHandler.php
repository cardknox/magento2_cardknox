<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;

class TxnIdHandler implements HandlerInterface
{
    public const XREFNUM = 'xRefNum';
    public const XMASKEDCARDNUMBER = 'xMaskedCardNumber';
    public const XAVSRESULT = 'xAvsResult';
    public const XCVVRESULT = 'xCvvResult';
    public const XCARDTYPE = 'xCardType';
    public const XTOKEN = 'xToken';
    public const XAUTHCODE = 'xAuthCode';
    public const XBATCH = 'xBatch';
    public const XAUTHAMOUNT = 'xAuthAmount';
    public const XSTATUS = 'xStatus';
    public const XERROR = 'xError';
    public const XEXP = 'xExp';
    public const XCVVRESULTCODE = 'xCvvResultCode';
    public const XAVSRESULTCODE = 'xAvsResultCode';

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
        self::XMASKEDCARDNUMBER,
        self::XAVSRESULT,
        self::XCVVRESULT,
        self::XCARDTYPE,
        self::XEXP,
        self::XBATCH,
        self::XREFNUM,
        self::XAUTHCODE,
        self::XAVSRESULTCODE,
        self::XCVVRESULTCODE,
        self::XAUTHAMOUNT
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
        $payment->setTransactionId($response[$this::XREFNUM]);
        $payment->setIsTransactionClosed(false);
        if ($payment->getLastTransId() == '') {
            foreach ($this->additionalInformationMapping as $item) {
                if (!isset($response[$item])) {
                    continue;
                }
                $payment->setAdditionalInformation($item, $response[$item]);
            }
        } else {
            if (isset($response[self::XBATCH])) {
                //batch only gets added after capturing
                $payment->setAdditionalInformation(self::XBATCH, $response[self::XBATCH]);
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
        $mapper = $this->config->getCctypesMapper();
        return $mapper[$type];
    }
}
