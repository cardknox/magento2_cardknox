<?php
namespace CardknoxDevelopment\Cardknox\Test\Unit\Helper;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Store\Model\ScopeInterface;
use CardknoxDevelopment\Cardknox\Helper\Data;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

#[AllowMockObjectsWithoutExpectations]
class DataTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress&MockObject
     */
    private $remoteAddress;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $contextStub = $this->createStub(Context::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $contextStub->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $isSingleSourceModeStub = $this->createStub(IsSingleSourceModeInterface::class);

        $this->helper = new Data($contextStub, $this->remoteAddress, $isSingleSourceModeStub);
    }

    public function testFormatPrice()
    {
        $this->assertEquals('1.00', $this->helper->formatPrice(1.00));
        $this->assertEquals('10.50', $this->helper->formatPrice(10.5));
        $this->assertEquals('0.00', $this->helper->formatPrice(0));
    }

    public function testIsCCSplitCaptureEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_CC_SPLIT_CAPTURE_ENABLED, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn('1');

        $this->assertEquals('1', $this->helper->isCCSplitCaptureEnabled());
    }

    public function testIsGPaySplitCaptureEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_GPAY_SPLIT_CAPTURE_ENABLED, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn('1');

        $this->assertEquals('1', $this->helper->isGPaySplitCaptureEnabled());
    }

    public function testGetIpAddress()
    {
        $ipAddress = '127.0.0.1';
        $this->remoteAddress->expects($this->once())
            ->method('getRemoteAddress')
            ->with(false)
            ->willReturn($ipAddress);

        $this->assertEquals($ipAddress, $this->helper->getIpAddress());
    }

    public function testIsCardknoxGiftcardEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_CARDKNOX_GIFTCARD_ENABLED, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn('1');

        $this->assertEquals('1', $this->helper->isCardknoxGiftcardEnabled());
    }

    public function testCardknoxGiftcardText()
    {
        $text = 'Test Gift Card Text';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_CARDKNOX_GIFTCARD_TEXT, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn($text);

        $this->assertEquals($text, $this->helper->cardknoxGiftcardText());
    }

    public function testGetConfigValue()
    {
        $key = 'payment/cardknox/title';
        $value = 'Credit Card';
        $storeId = 1;

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with($key, $storeId)
            ->willReturn($value);

        $this->assertEquals($value, $this->helper->getConfigValue($key, $storeId));
    }
}
