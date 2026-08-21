<?php

namespace CardknoxDevelopment\Cardknox\Gateway\Request;

use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Gateway\Request\RefundRequest;
use CardknoxDevelopment\Cardknox\Helper\Data;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
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

    /**
     * @var ConfigInterface
     */
    private $configMock;

    /**
     * @var PaymentDataObjectInterface
     */
    private $paymentDO;

    /**
     * @var Payment
     */
    private $paymentModel;

    /**
     * @var RefundRequest
     */
    private $refundRequest;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper->method('formatPrice')
            ->willReturnCallback(static function ($price) {
                return sprintf('%.2F', $price);
            });
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
        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        // Refunding the full order total voids rather than refunds
        $this->stubOrderGrandTotal($amount);

        $this->assertEquals(
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

        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        // Refunding less than the order total issues a refund rather than a void
        $this->stubOrderGrandTotal('20.00');

        $this->assertEquals(
            $expectation,
            $this->refundRequest->build($buildSubject)
        );
    }

    /**
     * Stub the order the payment belongs to so it reports the given base grand total
     *
     * @param string $grandTotal
     * @return void
     */
    private function stubOrderGrandTotal($grandTotal)
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->method('getBaseGrandTotal')->willReturn($grandTotal);

        $this->paymentModel->method('getOrder')->willReturn($order);
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
