<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Response;

use CardknoxDevelopment\Cardknox\Gateway\Response\TxnIdHandler;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;

use Magento\Sales\Model\Order\Payment;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObject;

class TxnIdHandlerTest extends \PHPUnit_Framework_TestCase
{


    protected $paymentTokenFactory;
    protected $paymentExtensionFactory;
    protected $config;
    protected $request;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtension|MockObject
     */
    private $paymentExtension;

    /**
     * @var \Magento\Sales\Model\Order\Payment|MockObject
     */
    private $payment;

    /**
     * @var PaymentTokenInterface|MockObject
     */
    protected $paymentToken;


    protected function setUP()
    {



        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['__wakeup'])
            ->getMock();

        $mapperArray = [
            "Amex" => "AE",
            "Disc" => "DI",
            "JCB" => "JCB",
            "MasterCard" => "MC",
            "Visa" => "VI",
            "MI" => "MI",
            "Diners" => "DN",
            "CUP" => "CUP"
        ];
        $this->config = $this->getMockBuilder(Config::class)
            ->setMethods(['getCctypesMapper'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new TxnIdHandler(
            $this->paymentTokenFactory,
            $this->paymentExtensionFactory,
            $this->config
        );
    }


    public function testHandle()
    {

        $response = [
            TxnIdHandler::xRefNum => 'fcd7f001e9274fdefb14bff91c799306',
            TxnIdHandler::xMaskedCardNumber => '4xxxxx4444',
            TxnIdHandler::xAvsResult => 'Street Match',
            TxnIdHandler::xCvvResult => 'Match',
            TxnIdHandler::xCardType => 'Visa',
            TxnIdHandler::xToken => 'rh3gd4',
            TxnIdHandler::xAuthCode => 'xAuthCode',
            TxnIdHandler::xBatch => 'xBatch',
            TxnIdHandler::xAuthAmount => 'xAuthAmount',
            TxnIdHandler::xStatus => 'xStatus',
            TxnIdHandler::xError => 'xError',
            TxnIdHandler::xExp => '0122'
        ];



        $paymentData = $this->getPaymentDataObjectMock();
        $subject = ['payment' => $paymentData];



        $this->request->handle($subject, $response);
    }

    /**
     * Create mock for payment data object and order payment
     * @return MockObject
     */
    private function getPaymentDataObjectMock()
    {
        $mock = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->once())
            ->method('getPayment')
            ->method('getPayment')
            ->willReturn($this->payment);

        return $mock;
    }
}
