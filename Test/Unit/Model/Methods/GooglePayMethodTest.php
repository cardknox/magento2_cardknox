<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Methods;

use CardknoxDevelopment\Cardknox\Model\Methods\GooglePayMethod;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Payment\Model\Method\AbstractMethod;

class GooglePayMethodTest extends TestCase
{
    /**
     * @var GooglePayMethod
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(GooglePayMethod::class);
    }

    /**
     * Test that the payment method code is set correctly
     */
    public function testPaymentMethodCode()
    {
        $this->assertEquals(GooglePayMethod::CODE, $this->model->getCode());
        $this->assertEquals('cardknox_google_pay', $this->model->getCode());
    }

    /**
     * Test that GooglePayMethod extends AbstractMethod
     */
    public function testClassInheritance()
    {
        $this->assertInstanceOf(AbstractMethod::class, $this->model);
    }

    /**
     * Test constant value
     */
    public function testConstantValue()
    {
        $this->assertEquals('cardknox_google_pay', GooglePayMethod::CODE);
    }
}
