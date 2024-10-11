<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace CardknoxDevelopment\Cardknox\Model\Ui\Adminhtml;

use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use CardknoxDevelopment\Cardknox\Gateway\Response\VaultHandler;

/**
 * Class TokenProvider
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var VaultHandler
     */
    private $vaultHandler;

    /**
     * TokenUiComponentProvider
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param VaultHandler $vaultHandler
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        VaultHandler $vaultHandler,
        UrlInterface $urlBuilder
    ) {
        $this->componentFactory = $componentFactory;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        $data = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $hash = $paymentToken->getPublicHash();
        if ($hash == "") {
            $hash = $this->vaultHandler->generatePublicHash($paymentToken);
            $paymentToken->setPublicHash($hash);
            $paymentToken->save();
        }
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => ConfigProvider::CC_VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $data,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'CardknoxDevelopment_Cardknox::form/vault.phtml'
                ],
                'name' => Template::class
            ]
        );

        return $component;
    }
}
