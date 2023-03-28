<?php

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

require __DIR__ . "/../Gateway/Requests/RefundRequest.php";

use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Gateway\Request\RefundRequest;
use CardknoxDevelopment\Cardknox\Helper\Data;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;

class RefundRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Logger
     */
    private $logger;
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
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->refundRequest = new RefundRequest($this->logger, $this->helper);
    }

    public function testBuild()
    {
        $amount = "10.00";
        $command = "cc:voidrefund";
        $refnum = '23443535';

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];

        $expectation = [
            'xCommand' => $command,
            'xAmount'   => $this->helper->formatPrice($amount),
            'xRefNum' => null,
        ];
        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        static::assertEquals(
            $expectation,
            $this->refundRequest->build($buildSubject)
        );
    }

    public function testBuildRefund()
    {
        $amount = "10.00";
        $command = "cc:refund";
        $refnum = '23443535';
        $expectation = [
            'xCommand' => $command,
            'xAmount'   => $this->helper->formatPrice($amount),
            'xRefNum' => null,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => $amount
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects(static::any())
            ->method('getGrandTotalAmount')
            ->willReturn($amount);

        static::assertEquals(
            $expectation,
            $this->refundRequest->build($buildSubject)
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
        $this->refundRequest->build($buildSubject);
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
        $this->refundRequest->build($buildSubject);
    }
}
