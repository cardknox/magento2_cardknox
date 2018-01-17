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

class CaptureRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {

        $amount = 1;
        $storeId = 1;
        $refnum = '23443535';

        $expectation = [
            'xCommand' => 'cc:capture',
            'xAmount' => $amount,
            'xRefNum' => $refnum
        ];

        $configMock = $this->getMock(ConfigInterface::class);
        $orderMock = $this->getMock(OrderAdapterInterface::class);
        $paymentDO = $this->getMock(PaymentDataObjectInterface::class);
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

//        $orderMock->expects(self::once())
//            ->method('getGrandTotalAmount')
//            ->willReturn($amount);


        /** @var ConfigInterface $configMock */
        $request = new CaptureRequest($configMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentDO, 'amount' => 1])
        );
    }
}
