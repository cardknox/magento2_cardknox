<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Gateway\Request;

use CardknoxDevelopment\Cardknox\Gateway\Request\CaptureRequest;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

class CaptureRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {

        $amount = "1.00";
        $storeId = 1;
        $refnum = '23443535';

        $expectation = [
            'xCommand' => 'cc:capture',
            'xAmount' => $amount,
            'xRefNum' => $refnum,
            'xIgnoreInvoice' => true
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

        $paymentModel->expects(static::exactly(2))
            ->method('getLastTransId')
            ->willReturn($refnum);

        $orderMock->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        /** @var ConfigInterface $configMock */
        $request = new CaptureRequest($configMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentDO, 'amount' => 1])
        );
    }
}
