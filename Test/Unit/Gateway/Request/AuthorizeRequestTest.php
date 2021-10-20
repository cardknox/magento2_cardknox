<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Request;

use CardknoxDevelopment\Cardknox\Gateway\Request\AuthorizationRequest;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CardknoxDevelopment\Cardknox\Observer\DataAssignObserver;

class AuthorizeRequestTest extends \PHPUnit\Framework\TestCase
{

    private $payment;
    private $paymentDO;
    private $authorizationRequest;
    private $order;
    const xCardNum = '4sdfssdfsdfdsf1111';
    const xCVV = "ewerwre2345";
    const cc_exp_month = 10;
    const cc_exp_year = 2018;

    protected function setUp(): void
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorizationRequest = new AuthorizationRequest($this->payment);
    }

    public function testBuild()
    {
        $invoiceId = 1001;
        $currencyCode = 'USD';
        $amount = "10.00";

        $additionalData = [
            [
                DataAssignObserver::xCardNum,
                self::xCardNum
            ],
            [
                DataAssignObserver::xCVV,
                self::xCVV
            ],[
                DataAssignObserver::cc_exp_month,
                self::cc_exp_month,
            ],[
                DataAssignObserver::cc_exp_year,
                self::cc_exp_year
            ],
        ];

        $expectation = [
            'xCommand' => 'cc:authonly',
            'xInvoice' => $invoiceId,
            'xCurrency' => $currencyCode,
            'xExp' => sprintf('%02d%02d', self::cc_exp_month, substr(self::cc_exp_year, -2)),
            'xCVV' => self::xCVV,
            'xCardNum' => self::xCardNum,
            'xAmount' => $amount,
            'xIgnoreInvoice' => true,
            'xTimeoutSeconds' => 55
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];

        $this->payment->expects(static::exactly(4))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);

        $this->order->expects(static::once())
            ->method('getOrderIncrementId')
            ->willReturn($invoiceId);

        $this->order->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn($currencyCode);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->order);

//        $this->order->expects(self::once())
//            ->method('getGrandTotalAmount')
//            ->willReturn($amount);

        static::assertEquals(
            $expectation,
            $this->authorizationRequest->build($buildSubject)
        );
    }
}
