<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        //make response variables nicer
        $field = ltrim($field, 'x');
        $splitFields = preg_split('/(?<=[a-z])(?=[A-Z])/', $field);
        return __(implode(" ", $splitFields));
    }
}
