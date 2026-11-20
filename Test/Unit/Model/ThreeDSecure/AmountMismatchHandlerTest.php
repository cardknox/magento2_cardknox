<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\ThreeDSecure;

use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AmountMismatchHandler;
use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AuthorizationVoider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AmountMismatchHandlerTest extends TestCase
{
    private const STORE_ID = 3;
    private const QUOTE_ID = 55;
    private const REF_NUM = '1234567890';

    /** @var AuthorizationVoider&MockObject */
    private $voider;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var AmountMismatchHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->voider = $this->createMock(AuthorizationVoider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = (new ObjectManager($this))->getObject(
            AmountMismatchHandler::class,
            [
                'authorizationVoider' => $this->voider,
                'logger' => $this->logger,
            ]
        );
    }

    public function testItVoidsTheAuthorizationUsingTheReferenceNumberFromTheResponse(): void
    {
        $this->voider->expects($this->once())
            ->method('void')
            ->with(self::REF_NUM, self::STORE_ID)
            ->willReturn(true);

        $this->handler->handle(['xRefNum' => self::REF_NUM], '100.00', self::QUOTE_ID, self::STORE_ID);
    }

    public function testItLogsAWarningWithReferenceNumberAndBothAmountsWhenTheVoidSucceeds(): void
    {
        $this->voider->method('void')->willReturn(true);
        $this->logger->expects($this->never())->method('critical');
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringStartsWith('Sola 3DS amount mismatch'),
                [
                    'ref_num' => self::REF_NUM,
                    'approved_amount' => '1.00',
                    'cart_amount' => '100.00',
                    'quote_id' => self::QUOTE_ID,
                    'store_id' => self::STORE_ID,
                    'void_succeeded' => true,
                ]
            );

        $this->handler->handle(
            ['xRefNum' => self::REF_NUM, 'xAuthAmount' => '1.00'],
            '100.00',
            self::QUOTE_ID,
            self::STORE_ID
        );
    }

    public function testItLogsACriticalEntryAskingForAManualVoidWhenTheVoidFails(): void
    {
        $this->voider->method('void')->willReturn(false);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                $this->logicalAnd(
                    $this->stringStartsWith('Sola 3DS amount mismatch'),
                    $this->stringContains('voided manually in the Sola portal')
                ),
                $this->callback(static fn (array $context): bool => $context['void_succeeded'] === false)
            );

        $this->handler->handle(
            ['xRefNum' => self::REF_NUM, 'xAuthAmount' => '1.00'],
            '100.00',
            self::QUOTE_ID,
            self::STORE_ID
        );
    }

    public function testItLogsTheApprovedAmountAsEmptyWhenItIsMissingFromTheResponse(): void
    {
        $this->voider->method('void')->willReturn(true);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->anything(),
                $this->callback(static fn (array $context): bool => $context['approved_amount'] === '')
            );

        $this->handler->handle(['xRefNum' => self::REF_NUM], '100.00', self::QUOTE_ID, self::STORE_ID);
    }

    public function testItDoesNotIncludeCardDataOrKeysInTheLogContext(): void
    {
        $this->voider->method('void')->willReturn(true);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->anything(),
                $this->callback(static function (array $context): bool {
                    return array_keys($context) === [
                        'ref_num',
                        'approved_amount',
                        'cart_amount',
                        'quote_id',
                        'store_id',
                        'void_succeeded',
                    ] && strpos(json_encode($context), '4111111111111111') === false;
                })
            );

        $this->handler->handle(
            [
                'xRefNum' => self::REF_NUM,
                'xAuthAmount' => '1.00',
                'xKey' => 'secret-key',
                'xCavv' => 'cavv-value',
                'xCardNum' => '4111111111111111',
                'xCVV' => '123',
            ],
            '100.00',
            self::QUOTE_ID,
            self::STORE_ID
        );
    }

    public function testItDoesNotThrowWhenTheVoidFails(): void
    {
        $this->voider->method('void')->willReturn(false);

        $this->handler->handle(['xRefNum' => self::REF_NUM], '100.00', self::QUOTE_ID, self::STORE_ID);

        $this->addToAssertionCount(1);
    }
}
