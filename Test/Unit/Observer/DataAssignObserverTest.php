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

    public const XCARDNUM = '4444333322221111';
    public const XCVV = '123';
    public const CC_EXP_MONTH = 10;
    public const CC_EXP_YEAR = 2018;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentInfoModel = $this->createMock(InfoInterface::class);
        $this->dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'xCardNum' => self::XCARDNUM,
                    'xCVV' => self::XCVV,
                    'cc_exp_month' => self::CC_EXP_MONTH,
                    'cc_exp_year' => self::CC_EXP_YEAR
                ]

            ]
        );
        $this->observer = new DataAssignObserver();
    }

    public function testExectute()
    {
        $this->observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->expects(static::exactly(2))
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::MODEL_CODE, $this->paymentInfoModel],
                    [AbstractDataAssignObserver::DATA_CODE, $this->dataObject]
                ]
            );

        $this->paymentInfoModel->expects(static::at(0))
            ->method('setAdditionalInformation')
            ->with(
                'xCardNum',
                self::XCARDNUM
            );
        $this->paymentInfoModel->expects(static::at(1))
            ->method('setAdditionalInformation')
            ->with(
                'xCVV',
                self::XCVV
            );

        $this->paymentInfoModel->expects(static::at(2))
            ->method('setAdditionalInformation')
            ->with(
                'cc_exp_month',
                self::CC_EXP_MONTH
            );

        $this->paymentInfoModel->expects(static::at(3))
            ->method('setAdditionalInformation')
            ->with(
                'cc_exp_year',
                self::CC_EXP_YEAR
            );
        
        $this->observer->execute($this->observerContainer);
    }
}
