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
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var PaymentDataObjectInterface
     */
    private $paymentDO;

    /**
     *
     * @var AuthorizationRequest
     */
    private $authorizationRequest;

    /**
     * @var OrderAdapterInterface
     */
    private $order;

    public const XCARDNUM = '4sdfssdfsdfdsf1111';
    public const XCVV = "ewerwre2345";
    public const CC_EXP_MONTH = 10;
    public const CC_EXP_YEAR = 2018;

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
                DataAssignObserver::XCARDNUM,
                self::XCARDNUM
            ],
            [
                DataAssignObserver::XCVV,
                self::XCVV
            ],[
                DataAssignObserver::CC_EXP_MONTH,
                self::CC_EXP_MONTH,
            ],[
                DataAssignObserver::CC_EXP_YEAR,
                self::CC_EXP_YEAR
            ],
        ];

        $expectation = [
            'xCommand' => 'cc:authonly',
            'xInvoice' => $invoiceId,
            'xCurrency' => $currencyCode,
            'xExp' => sprintf('%02d%02d', self::CC_EXP_MONTH, substr(self::CC_EXP_YEAR, -2)),
            'xCVV' => self::XCVV,
            'xCardNum' => self::XCARDNUM,
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

        static::assertEquals(
            $expectation,
            $this->authorizationRequest->build($buildSubject)
        );
    }
}
