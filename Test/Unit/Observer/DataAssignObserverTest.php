<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use CardknoxDevelopment\Cardknox\Observer\DataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{

    const xCardNum = '4444333322221111';
    const xCVV = '123';
    const cc_exp_month = 10;
    const cc_exp_year = 2018;


    public function testExectute()
    {
        $observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoModel = $this->createMock(InfoInterface::class);
        $dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'xCardNum' => self::xCardNum,
                    'xCVV' => self::xCVV,
                    'cc_exp_month' => self::cc_exp_month,
                    'cc_exp_year' => self::cc_exp_year
                ]

            ]
        );
        $observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($event);
        $event->expects(static::exactly(2))
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::MODEL_CODE, $paymentInfoModel],
                    [AbstractDataAssignObserver::DATA_CODE, $dataObject]
                ]
            );

        $paymentInfoModel->expects(static::at(0))
            ->method('setAdditionalInformation')
            ->with(
                'xCardNum',
                self::xCardNum
            );
        $paymentInfoModel->expects(static::at(1))
            ->method('setAdditionalInformation')
            ->with(
                'xCVV',
                self::xCVV
            );

        $paymentInfoModel->expects(static::at(2))
            ->method('setAdditionalInformation')
            ->with(
                'cc_exp_month',
                self::cc_exp_month
            );

        $paymentInfoModel->expects(static::at(3))
            ->method('setAdditionalInformation')
            ->with(
                'cc_exp_year',
                self::cc_exp_year
            );

        $observer = new DataAssignObserver();
        $observer->execute($observerContainer);
    }
}
