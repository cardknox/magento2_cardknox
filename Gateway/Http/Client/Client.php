<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Gateway\Http\Client;

use LogicException;
use RuntimeException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class Client implements ClientInterface
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Curl $curl
     * @param Logger $logger
     */
    public function __construct(
        Curl $curl,
        Logger $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * PlaceRequest function
     *
     * @param TransferInterface $transferObject
     * @return void
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => $transferObject->getBody(),
            'request_uri' => $transferObject->getUri()
        ];
        $result = [];
        
        $this->curl->setHeaders($transferObject->getHeaders());

        switch ($transferObject->getMethod()) {
            case Request::METHOD_GET:
                $this->curl->get($transferObject->getUri());
                break;
            case Request::METHOD_POST:
                $this->curl->post($transferObject->getUri(), $transferObject->getBody());
                break;
            default:
                throw new LogicException(
                    sprintf(
                        'Unsupported HTTP method %s',
                        $transferObject->getMethod()
                    )
                );
        }

        try {
            //phpcs:disable
            parse_str($this->curl->getBody(), $result);
            //phpcs:enable
            $log['response'] = $result;
        } catch (RuntimeException $e) {
            throw new ClientException(__($e->getMessage()));
        } catch (ConverterException $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
