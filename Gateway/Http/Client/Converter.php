<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ConverterInterface;

class Converter implements ConverterInterface
{

    /**
     * Converts gateway response to ENV structure
     *
     * @param mixed $response
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function convert($response)
    {
        parse_str($response, $result);
        return $result;
    }
}
