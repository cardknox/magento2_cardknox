<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
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
     * @var Event\Observer
     */
    private $observerContainer;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var InfoInterface
     */
    private $paymentInfoModel;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var DataAssignObserver
     */
    private $observer;

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

        // Account for all the setAdditionalInformation calls in the observer
        $this->paymentInfoModel->expects($this->exactly(6))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['xCardNum', self::XCARDNUM],
                ['xCVV', self::XCVV],
                ['cc_exp_month', self::CC_EXP_MONTH],
                ['cc_exp_year', self::CC_EXP_YEAR],
                ['xAmount', null],
                ['shippingAddressFirstname', null]
            );

        $this->observer->execute($this->observerContainer);
    }
}
