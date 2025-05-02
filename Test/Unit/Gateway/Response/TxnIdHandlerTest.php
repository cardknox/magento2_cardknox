<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Response;

use CardknoxDevelopment\Cardknox\Gateway\Response\TxnIdHandler;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\CreditCardTokenFactory;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use PHPUnit\Framework\MockObject\MockObject;

class TxnIdHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $paymentTokenFactory;

    /**
     * @var string
     */
    protected $paymentExtensionFactory;
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
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

    protected function setUp(): void
    {
        $this->paymentToken = $this->createMock(PaymentTokenInterface::class);
        
        // Create the payment mock - removed setMethods()
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
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
            ->disableOriginalConstructor()
            ->getMock();

        $this->config->method('getCctypesMapper')
            ->willReturn($mapperArray);

        $this->request = new TxnIdHandler(
            $this->config
        );
    }

    /**
     * Test handle function
     *
     * @return void
     */
    public function testHandle()
    {
        $response = [
            TxnIdHandler::XREFNUM => 'fcd7f001e9274fdefb14bff91c799306',
            TxnIdHandler::XMASKEDCARDNUMBER => '4xxxxx4444',
            TxnIdHandler::XCVVRESULT => 'Match',
            TxnIdHandler::XCVVRESULTCODE => 'M',
            TxnIdHandler::XCARDTYPE => 'Visa',
            TxnIdHandler::XTOKEN => 'rh3gd4',
            TxnIdHandler::XAUTHCODE => 'xAuthCode',
            TxnIdHandler::XBATCH => 'xBatch',
            TxnIdHandler::XAUTHAMOUNT => 'xAuthAmount',
            TxnIdHandler::XSTATUS => 'xStatus',
            TxnIdHandler::XERROR => 'xError',
            TxnIdHandler::XEXP => '0122'
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
        // Changed to use createMock for simplicity
        $mock = $this->createMock(PaymentDataObject::class);

        $mock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->payment);

        return $mock;
    }

    public function testHandleException()
    {
        $amount = '10.00';
        $subject = [
            'payment' => null
        ];

        $response = [
            TxnIdHandler::XREFNUM => 'fcd7f001e9274fdefb14bff91c799306',
            TxnIdHandler::XMASKEDCARDNUMBER => '4xxxxx4444',
            TxnIdHandler::XCVVRESULT => 'Match',
            TxnIdHandler::XCVVRESULTCODE => 'M',
            TxnIdHandler::XCARDTYPE => 'Visa',
            TxnIdHandler::XTOKEN => 'rh3gd4',
            TxnIdHandler::XAUTHCODE => 'xAuthCode',
            TxnIdHandler::XBATCH => 'xBatch',
            TxnIdHandler::XAUTHAMOUNT => 'xAuthAmount',
            TxnIdHandler::XSTATUS => 'xStatus',
            TxnIdHandler::XERROR => 'xError',
            TxnIdHandler::XEXP => '0122'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');
        $this->request->handle($subject, $response);
    }
}
