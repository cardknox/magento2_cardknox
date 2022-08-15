<?php
/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Validator;

use CardknoxDevelopment\Cardknox\Gateway\Http\Client\Client;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Command\CommandException;

class ResponseCodeValidator extends AbstractValidator
{
    /**
     * Logger variable
     *
     * @var Logger
     */
    private $logger;

    /**
     * ResponseCodeValidator function
     *
     * @param Logger $logger
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        Logger $logger,
        ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->logger = $logger;
    }

    public const RESULT_CODE = 'xResult';
    public const DECLINE = 'D';
    public const ERROR = 'E';
    public const SUCCESS = 'A';
    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];
        $log['Successful Transaction'] = $this->isSuccessfulTransaction($response);
        $this->logger->debug($log);
        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(true);
        } else {
            return $this->createResult(
                false,
                $this->getFailedResponse($response),
                $this->getErrorCode($response)
            );
            // $errorMessage = $this->getFailedResponse($response);
            // $logError['Payment Error'] = $errorMessage;
            // $this->logger->debug([$logError]);
            // throw new CommandException(
            //     __(implode(PHP_EOL, $errorMessage))
            // );
        }
    }

    /**
     * IsSuccessfulTransaction
     *
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        return isset($response[self::RESULT_CODE])
        && $response[self::RESULT_CODE] == self::SUCCESS;
    }

    /**
     * GetFailedResponse function
     *
     * @param array $response
     * @return void
     */
    private function getFailedResponse(array $response)
    {
        $errorMessage = (isset($response['xError']) ? $response['xError'] : "");
        $refnum = (isset($response['xRefNum']) ? $response['xRefNum'] : "");
        return [__($errorMessage . " " . $refnum)];
    }

    /**
     * GetErrorCode function
     *
     * @param array $response
     * @return void
     */
    private function getErrorCode(array $response)
    {
        $errorCode = (isset($response['xErrorCode']) ? $response['xErrorCode'] : "");
        return [__($errorCode)];
    }
}
