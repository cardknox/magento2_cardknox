<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Request;

require "Gateway/Requests/AuthorizationRequest.php";

use CardknoxDevelopment\Cardknox\Gateway\Request\AuthorizationRequest;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CardknoxDevelopment\Cardknox\Observer\DataAssignObserver;
use CardknoxDevelopment\Cardknox\Helper\Data;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;

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

    /**
     * @var Data
     */
    private $helper;

    /**
     *
     * @var Config
     */
    protected $config;

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
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorizationRequest = new AuthorizationRequest($this->helper, $this->config);
    }

    public function testBuild()
    {
        $invoiceId = 1001;
        $currencyCode = 'USD';
        $amount = "10.00";
        $ccPaymentAction = 'authorize';

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
        $isCCSplitCaptureEnabled = 1;
        $xRequireSplitCapturable= 0;

        $expectation = [
            'xAmount' => $this->helper->formatPrice($amount),
            'xExp' => sprintf('%02d%02d', self::CC_EXP_MONTH, substr(self::CC_EXP_YEAR, -2)),
            'xCVV' => self::XCVV,
            'xCommand' => 'cc:authonly',
            'xInvoice' => $invoiceId,
            'xCurrency' => $currencyCode,
            'xCardNum' => self::XCARDNUM,
            'xIgnoreInvoice' => true,
            'xTimeoutSeconds' => 55,
            'xRequireSplitCapturable' => $xRequireSplitCapturable
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];

        $this->payment->expects(static::exactly(6))
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

    public function testBuildException()
    {
        $amount = '10.00';
        $buildSubject = [
            'payment' => null,
            'amount' => $amount
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');
        $this->authorizationRequest->build($buildSubject);
    }
}
