<?php
/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\Ui;

use CardknoxDevelopment\Cardknox\Model\Ui\ConfigProvider;
use CardknoxDevelopment\Cardknox\Model\Ui\TokenUiComponentProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenUiComponentProviderTest extends TestCase
{
    /**
     * Constants
     */
    private const VAULT_COMPONENT_PATH = 'CardknoxDevelopment_Cardknox/js/view/payment/method-renderer/vault';

    /**
     * @var TokenUiComponentProvider
     */
    private $tokenUiComponentProvider;

    /**
     * @var TokenUiComponentInterfaceFactory|MockObject
     */
    private $componentFactory;

    /**
     * @var PaymentTokenInterface|MockObject
     */
    private $paymentToken;

    /**
     * @var TokenUiComponentInterface|MockObject
     */
    private $tokenUiComponent;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        // Replace setMethods() with createMock
        $this->componentFactory = $this->createMock(TokenUiComponentInterfaceFactory::class);
        $this->paymentToken = $this->createMock(PaymentTokenInterface::class);
        $this->tokenUiComponent = $this->createMock(TokenUiComponentInterface::class);

        $this->tokenUiComponentProvider = $this->objectManager->getObject(
            TokenUiComponentProvider::class,
            [
                'componentFactory' => $this->componentFactory
            ]
        );
    }

    /**
     * Helper method to set up common expectations and execute the test
     *
     * @param string|null $tokenDetails The token details JSON string
     * @param string $publicHash The public hash value
     * @param array|null $expectedJsonDetails The expected decoded JSON details
     * @return void
     */
    private function executeComponentTest($tokenDetails, $publicHash, $expectedJsonDetails)
    {
        $this->paymentToken->expects($this->once())
            ->method('getTokenDetails')
            ->willReturn($tokenDetails);

        $this->paymentToken->expects($this->once())
            ->method('getPublicHash')
            ->willReturn($publicHash);

        $this->componentFactory->expects($this->once())
            ->method('create')
            ->with([
                'config' => [
                    'code' => ConfigProvider::CC_VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $expectedJsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $publicHash
                ],
                'name' => self::VAULT_COMPONENT_PATH
            ])
            ->willReturn($this->tokenUiComponent);

        $result = $this->tokenUiComponentProvider->getComponentForToken($this->paymentToken);
        $this->assertSame($this->tokenUiComponent, $result);
    }

    /**
     * Test getComponentForToken with token details
     */
    public function testGetComponentForToken()
    {
        $tokenDetails = '{"maskedCC":"411111******1111","expirationDate":"01/2025","type":"VI"}';
        $publicHash = 'public_hash_value';
        $jsonDetails = json_decode($tokenDetails, true);

        $this->executeComponentTest($tokenDetails, $publicHash, $jsonDetails);
    }

    /**
     * Test getComponentForToken with empty token details
     */
    public function testGetComponentForTokenWithEmptyDetails()
    {
        $tokenDetails = null;
        $publicHash = 'public_hash_value';
        $jsonDetails = []; // json_decode('{}', true) returns an empty array

        $this->executeComponentTest($tokenDetails, $publicHash, $jsonDetails);
    }

    /**
     * Test getComponentForToken with invalid JSON
     */
    public function testGetComponentForTokenWithInvalidJson()
    {
        $tokenDetails = '{invalid-json}';
        $publicHash = 'public_hash_value';
        $jsonDetails = null; // json_decode returns null for invalid JSON

        $this->executeComponentTest($tokenDetails, $publicHash, $jsonDetails);
    }
}
