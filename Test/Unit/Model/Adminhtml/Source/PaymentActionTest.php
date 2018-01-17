<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Adminhtml\Source;

use CardknoxDevelopment\Cardknox\Model\Adminhtml\Source\PaymentAction;

class PaymentActionTest extends \PHPUnit_Framework_TestCase
{
    public function testToOptionArray()
    {
        $sourceModel = new PaymentAction();

        static::assertEquals(
            [
                [
                    'value' => 'authorize',
                    'label' => __('Authorize'),
                ],
                [
                    'value' => 'authorize_capture',
                    'label' => __('Authorize and Capture'),
                ],
            ],
            $sourceModel->toOptionArray()
        );
    }
}
