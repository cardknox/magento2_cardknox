<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Test\Unit\Model\ThreeDSecure;

use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AuthorizedAmountValidator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthorizedAmountValidatorTest extends TestCase
{
    /**
     * @var AuthorizedAmountValidator
     */
    private AuthorizedAmountValidator $validator;

    protected function setUp(): void
    {
        $this->validator = (new ObjectManager($this))->getObject(AuthorizedAmountValidator::class);
    }

    /**
     * Build a quote mock returning the given base grand total.
     *
     * @param mixed $total
     * @return Quote
     */
    private function createQuote(mixed $total): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getData')->with('base_grand_total')->willReturn($total);

        return $quote;
    }

    public function testItReturnsTrueWhenApprovedAmountEqualsCartBaseGrandTotal(): void
    {
        $this->assertTrue($this->validator->isMatch(['xAuthAmount' => '10.50'], $this->createQuote('10.5000')));
    }

    public function testItReturnsTrueWhenAmountsDifferOnlyInFormattingSuchAs10Point5And10Point50(): void
    {
        $this->assertTrue($this->validator->isMatch(['xAuthAmount' => 10.5], $this->createQuote('10.50')));
        $this->assertTrue($this->validator->isMatch(['xAuthAmount' => '10.50'], $this->createQuote(10.5)));
    }

    public function testItReturnsFalseWhenApprovedAmountIsLowerThanCartBaseGrandTotal(): void
    {
        $this->assertFalse($this->validator->isMatch(['xAuthAmount' => '10.00'], $this->createQuote('10.50')));
    }

    public function testItReturnsFalseWhenApprovedAmountIsHigherThanCartBaseGrandTotal(): void
    {
        $this->assertFalse($this->validator->isMatch(['xAuthAmount' => '11.00'], $this->createQuote('10.50')));
    }

    public function testItReturnsFalseWhenApprovedAmountIsMissingFromTheResponse(): void
    {
        $this->assertFalse($this->validator->isMatch([], $this->createQuote('10.50')));
    }

    #[DataProvider('invalidAmountProvider')]
    public function testItReturnsFalseWhenApprovedAmountIsEmptyOrNonNumeric(mixed $amount): void
    {
        $this->assertFalse($this->validator->isMatch(['xAuthAmount' => $amount], $this->createQuote('10.50')));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function invalidAmountProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'text' => ['abc'],
            'null' => [null],
            'array' => [['10.50']],
        ];
    }

    public function testItReturnsFalseWhenApprovedAmountIsMissingAndTheCartTotalIsZero(): void
    {
        $this->assertFalse($this->validator->isMatch([], $this->createQuote('0.0000')));
        $this->assertFalse($this->validator->isMatch(['xAuthAmount' => ''], $this->createQuote(null)));
    }

    #[DataProvider('nonPositiveAmountProvider')]
    public function testItReturnsFalseWhenApprovedAmountIsZeroOrNegativeEvenIfTheCartTotalIsZero(
        mixed $amount
    ): void {
        $this->assertFalse($this->validator->isMatch(['xAuthAmount' => $amount], $this->createQuote('0.0000')));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function nonPositiveAmountProvider(): array
    {
        return [
            'zero' => ['0'],
            'zero decimal' => ['0.00'],
            'negative' => ['-5.00'],
            'zero float' => [0.0],
        ];
    }

    public function testItReturnsTheCartBaseGrandTotalFormattedToTwoDecimals(): void
    {
        $this->assertSame('10.50', $this->validator->getCartAmount($this->createQuote('10.5')));
    }

    public function testItReturnsZeroFormattedToTwoDecimalsWhenTheCartHasNoBaseGrandTotal(): void
    {
        $this->assertSame('0.00', $this->validator->getCartAmount($this->createQuote(null)));
    }
}
