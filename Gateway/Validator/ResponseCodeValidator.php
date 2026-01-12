<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config as SystemConfig;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Command\CommandException;

class ResponseCodeValidator extends AbstractValidator
{
    public const RESULT_CODE = 'xResult';
    public const DECLINE = 'D';
    public const ERROR = 'E';
    public const SUCCESS = 'A';
    public const VERIFY = 'V';

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Logger variable
     *
     * @var Logger
     */
    private $logger;

    /**
     * ResponseCodeValidator function
     *
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \CardknoxDevelopment\Cardknox\Gateway\Config\Config $systemConfig
     * @param Logger $logger
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SystemConfig $systemConfig,
        Logger $logger
    ) {
        parent::__construct($resultFactory);
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
    }

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

        if ($this->systemConfig->isEnable3DSecure() &&
            !empty($this->systemConfig->get3DSecureEnvironment()) &&
            $this->isVerifyTransaction($response)
        ) {
            // Trigger 3DS verification response handling
            return $this->processThreeDSResponse($response);
        }

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
     * @return array
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
     * @return array
     */
    private function getErrorCode(array $response)
    {
        $errorCode = (isset($response['xErrorCode']) ? $response['xErrorCode'] : "");
        return [__($errorCode)];
    }

    /**
     * Validate API response.
     *
     * @param array $response
     * @return array
     */
    protected function validateApiResponse(array $response): array
    {
        $isValid = true;
        $fails = [];

        // Check if verification is required or there are generic errors
        $isVerifyRequired = isset($response['xStatus'], $response['xResult'])
            && $response['xStatus'] === 'Verify'
            && $response['xResult'] === 'V';
        $hasErrorCode = isset($response['xErrorCode']) && $response['xErrorCode'] !== '00000';

        if ($isVerifyRequired || $hasErrorCode) {
            $fails['requires_verification'] = $response;
            $isValid = false;
        }

        return [
            'is_valid' => $isValid,
            'fails' => $fails
        ];
    }

    /**
     * Process the API response and return a result if needed
     *
     * @param mixed $response
     * @return ResultInterface
     */
    protected function processThreeDSResponse($response)
    {
        $validationResult = $this->validateApiResponse($response);

        if (!$validationResult['is_valid'] || !empty($validationResult['fails'])) {
            return $this->createResult($validationResult['is_valid'], $validationResult['fails']);
        }

        return $this->createResult(true, []);
    }

    /**
     * IsVerifyTransaction
     *
     * @param array $response
     * @return bool
     */
    private function isVerifyTransaction(array $response)
    {
        return isset($response[self::RESULT_CODE])
        && $response[self::RESULT_CODE] == self::VERIFY;
    }
}
