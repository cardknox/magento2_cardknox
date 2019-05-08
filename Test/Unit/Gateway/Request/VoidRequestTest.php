<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
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
    public function testBuild()
    {

        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $storeId = 1;

        $expectation = [
            'xCommand' => 'cc:void',
            'xRefNum' => $txnId,
        ];

        $configMock = $this->createMock(ConfigInterface::class);
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentModel);

        $paymentModel->expects(static::once())
            ->method('getParentTransactionId')
            ->willReturn($txnId);

        $orderMock->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        /** @var ConfigInterface $configMock */
        $request = new VoidRequest($configMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentDO])
        );
    }
}
