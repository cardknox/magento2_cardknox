<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\ThreeDSecure;

use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Gateway\Http\Client\Client;
use CardknoxDevelopment\Cardknox\Gateway\Http\TransferFactory;
use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AuthorizationVoider;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Gateway\Http\TransferInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class AuthorizationVoiderTest extends TestCase
{
    private const STORE_ID = 3;

    /** @var TransferFactory&MockObject */
    private $transferFactory;

    /** @var Client&MockObject */
    private $client;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var AuthorizationVoider */
    private $voider;

    protected function setUp(): void
    {
        $this->transferFactory = $this->createMock(TransferFactory::class);
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $config = $this->createMock(Config::class);
        $config->method('getValue')
            ->with('cardknox_transaction_key', self::STORE_ID)
            ->willReturn('secret-key');

        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getEdition')->willReturn('Community');
        $metadata->method('getVersion')->willReturn('2.4.8');

        $this->voider = (new ObjectManager($this))->getObject(
            AuthorizationVoider::class,
            [
                'transferFactory' => $this->transferFactory,
                'client' => $this->client,
                'config' => $config,
                'productMetadata' => $metadata,
                'logger' => $this->logger,
            ]
        );
    }

    private function expectRequest(callable $assertion, $response = ['xResult' => 'A']): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $this->transferFactory->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (array $request) use ($assertion, $transfer) {
                $assertion($request);

                return $transfer;
            });
        $this->client->method('placeRequest')->with($transfer)->willReturn($response);
    }

    public function testItSendsACcVoidCommandWithTheGivenReferenceNumber(): void
    {
        $this->expectRequest(function (array $request): void {
            $this->assertSame('cc:void', $request['xCommand']);
            $this->assertSame('12345', $request['xRefNum']);
        });

        $this->voider->void('12345', self::STORE_ID);
    }

    public function testItSendsTheStoreTransactionKeyAndSoftwareIdentificationFields(): void
    {
        $this->expectRequest(function (array $request): void {
            $this->assertSame('secret-key', $request['xKey']);
            $this->assertSame('5.0.0', $request['xVersion']);
            $this->assertSame('Magento Community 2.4.8', $request['xSoftwareName']);
            $this->assertSame('1.2.92', $request['xSoftwareVersion']);
        });

        $this->voider->void('12345', self::STORE_ID);
    }

    public function testItSendsThe64BitReferenceNumberSupportFlag(): void
    {
        $this->expectRequest(function (array $request): void {
            $this->assertTrue($request['xSupports64BitRefnum']);
        });

        $this->voider->void('12345', self::STORE_ID);
    }

    public function testItReturnsTrueWhenTheGatewayApprovesTheVoid(): void
    {
        $this->expectRequest(static function (array $request): void {
        });

        $this->assertTrue($this->voider->void('12345', self::STORE_ID));
    }

    #[DataProvider('failedResponses')]
    public function testItReturnsFalseWhenTheGatewayDeclinesOrErrorsTheVoid($response): void
    {
        $this->expectRequest(static function (array $request): void {
        }, $response);

        $this->assertFalse($this->voider->void('12345', self::STORE_ID));
    }

    public static function failedResponses(): array
    {
        return [
            'declined' => [['xResult' => 'D']],
            'error' => [['xResult' => 'E']],
            'missing result' => [[]],
            'not an array' => [null],
        ];
    }

    public function testItReturnsFalseAndLogsWhenTheGatewayClientThrowsAnException(): void
    {
        $this->transferFactory->method('create')->willReturn($this->createMock(TransferInterface::class));
        $this->client->method('placeRequest')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error');

        $this->assertFalse($this->voider->void('12345', self::STORE_ID));
    }

    public function testItReturnsFalseWithoutCallingTheGatewayWhenTheReferenceNumberIsEmpty(): void
    {
        $this->transferFactory->expects($this->never())->method('create');
        $this->client->expects($this->never())->method('placeRequest');

        $this->assertFalse($this->voider->void('', self::STORE_ID));
    }
}
