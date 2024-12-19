<?php

namespace CardknoxDevelopment\Cardknox\Gateway\Command;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config as SystemConfig;
use Psr\Log\LoggerInterface;

class GatewayCommand implements CommandInterface
{

    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ErrorMessageMapperInterface
     */
    private $errorMessageMapper;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Magento\Payment\Gateway\Http\TransferFactoryInterface $transferFactory
     * @param \Magento\Payment\Gateway\Http\ClientInterface $client
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Payment\Gateway\Response\HandlerInterface|null $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface|null $validator
     * @param \Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface|null $errorMessageMapper
     * @param \CardknoxDevelopment\Cardknox\Gateway\Config\Config $systemConfig
     */
    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        LoggerInterface $logger,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null,
        SystemConfig $systemConfig
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->errorMessageMapper = $errorMessageMapper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return void
     * @throws ClientException
     * @throws ConverterException
     */
    public function execute(array $commandSubject)
    {
        // @TODO implement exceptions catching
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);
        if ($this->validator !== null) {
            $result = $this->validator->validate(
                array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }
    }

    /**
     * Tries to map error messages from validation result and logs processed message.
     * Throws an exception with mapped message or default error.
     *
     * @param ResultInterface $result
     * @throws CommandException
     */
    private function processErrors(ResultInterface $result)
    {
        if ($this->systemConfig->isEnable3DSecure() && !empty($this->systemConfig->get3DSecureEnvironment())) {
            return $this->process3DSErrors($result);
        }

        $messages = [];
        $errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());
        foreach ($errorsSource as $errorCodeOrMessage) {
            $errorCodeOrMessage = (string) $errorCodeOrMessage;

            // error messages mapper can be not configured if payment method doesn't have custom error messages.
            if ($this->errorMessageMapper !== null) {
                $mapped = (string) $this->errorMessageMapper->getMessage($errorCodeOrMessage);
                if (!empty($mapped)) {
                    $messages[] = $mapped;
                    $errorCodeOrMessage = $mapped;
                }
            }
            $this->logger->critical('Payment Error: ' . $errorCodeOrMessage);
        }

        throw new CommandException(
            !empty($messages)
                ? __(implode(PHP_EOL, $messages))
                : __('Transaction has been declined. Please try again later.')
        );
    }

    /**
     * Process Errors with Custom Logic
     *
     * @param ResultInterface $result
     * @throws LocalizedException
     */
    private function process3DSErrors(ResultInterface $result)
    {
        $errorsSource = $result->getFailsDescription();

        if (isset($errorsSource['requires_verification'])) {
            // Instead of throwing an exception, we encode and return the response directly
            $response = $errorsSource['requires_verification'];
            throw new LocalizedException(
                __('%1', http_build_query($response)) // Encode and pass the response in the exception message
            );
        }

        // Handle other cases if needed
        throw new LocalizedException(
            __('Transaction has been declined. Please try again later.')
        );
    }
}
