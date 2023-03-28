<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Request;

require __DIR__ . "../Gateway/Requests/AuthorizationRequest.php";

use CardknoxDevelopment\Cardknox\Gateway\Request\CaptureRequest;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CardknoxDevelopment\Cardknox\Helper\Data;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use InvalidArgumentException;

class CaptureRequestTest extends \PHPUnit\Framework\TestCase
{
    public const XCARDNUM = '4sdfssdfsdfdsf1111';
    public const XCVV = "ewerwre2345";
    public const CC_EXP_MONTH = 10;
    public const CC_EXP_YEAR = 2018;

    /**
     * @var Data
     */
    private $helper;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->captureRequest = new CaptureRequest($this->helper, $this->config);
    }

    /**
     *
     * @var Config
     */
    protected $config;

    public function testBuild()
    {
        $amount = "1.00";
        $storeId = 1;
        $refnum = '23443535';
        $currencyCode = 'USD';
        $invoiceId = 1001;

        $expectation = [
            'xCommand' => 'cc:capture',
            'xAmount' => $this->helper->formatPrice($amount),
            'xRefNum' => $refnum,
            'xIgnoreInvoice' => true
        ];

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        $this->paymentModel->expects(static::exactly(2))
            ->method('getLastTransId')
            ->willReturn($refnum);

        $this->orderMock->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);
        
        static::assertEquals(
            $expectation,
            $this->captureRequest->build(['payment' => $this->paymentDO, 'amount' => 1])
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
        $this->captureRequest->build($buildSubject);
    }

    public function testBuildLogicException()
    {
        $amount = '10.00';
        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Order payment should be provided.');
        $this->captureRequest->build($buildSubject);
    }
}
