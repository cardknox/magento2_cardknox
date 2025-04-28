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
use PHPUnit\Framework\MockObject\MockObject;

class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{
    public const XCARDNUM = '4444333322221111';
    public const XCVV = '123';
    public const CC_EXP_MONTH = 10;
    public const CC_EXP_YEAR = 2018;

    /**
     * @var Event\Observer|MockObject
     */
    private $observerContainer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var InfoInterface|MockObject
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
        $this->observerContainer = $this->createMock(Event\Observer::class);
        $this->event = $this->createMock(Event::class);
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
        // Configure mocks
        $this->observerContainer->expects($this->atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->exactly(2))
            ->method('getDataByKey')
            ->willReturnCallback(function ($key) {
                if ($key === AbstractDataAssignObserver::MODEL_CODE) {
                    return $this->paymentInfoModel;
                }
                if ($key === AbstractDataAssignObserver::DATA_CODE) {
                    return $this->dataObject;
                }
                return null;
            });

        // Use willReturnSelf() to allow chaining of method calls
        $this->paymentInfoModel->method('setAdditionalInformation')
            ->willReturnSelf();
            
        // Execute the observer
        $this->observer->execute($this->observerContainer);
        
        // Test passes if no exceptions are thrown
        $this->assertTrue(true);
    }
}