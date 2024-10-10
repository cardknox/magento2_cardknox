<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Request;

use CardknoxDevelopment\Cardknox\Gateway\Request\VoidRequest;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

class VoidRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigInterface
     */
    private $configMock;

    /**
     * @var OrderAdapterInterface
     */
    private $orderMock;

    /**
     * @var PaymentDataObjectInterface
     */
    private $paymentDO;

    /**
     * @var Payment
     */
    private $paymentModel;

    /**
     * @var VoidRequest
     */
    private $voidRequest;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigInterface $configMock */
        $this->voidRequest = new VoidRequest($this->configMock);
    }
    public function testBuild()
    {
        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $storeId = 1;

        $expectation = [
            'xCommand' => 'cc:void',
            'xRefNum' => $txnId,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentModel);

        $this->paymentModel->expects(static::once())
            ->method('getParentTransactionId')
            ->willReturn($txnId);

        $this->orderMock->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        static::assertEquals(
            $expectation,
            $this->voidRequest->build(['payment' => $this->paymentDO])
        );
    }

    public function testBuildException()
    {
        $buildSubject = [
            'payment' => null
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');
        $this->voidRequest->build($buildSubject);
    }

    public function testBuildLogicException()
    {
        $buildSubject = [
            'payment' => $this->paymentDO
        ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Order payment should be provided.');
        $this->voidRequest->build($buildSubject);
    }
}
