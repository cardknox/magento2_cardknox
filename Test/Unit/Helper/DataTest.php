<?php
namespace CardknoxDevelopment\Cardknox\Test\Unit\Helper;

use Magento\Framework\Module\Output\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use CardknoxDevelopment\Cardknox\Helper\Data;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class DataTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ScopeInterface&MockObject
     */
    private $scopeInterfaceMock;

    /**
     * @var Context&MockObject
     */
    private $contextMock;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ConfigInterface
     */
    private $_outputConfig;

    /**
     * @var RemoteAddress&MockObject
     */
    private $remoteAddress;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->scopeInterfaceMock = $this->getMockBuilder(ScopeInterface::class)
            ->addMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getScopeConfig'])
            ->addMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->remoteAddress = $this->getMockBuilder(RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_outputConfig = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfig);

        $this->helper = new Data($this->contextMock, $this->remoteAddress);
    }

    /**
     * @return ScopeConfigInterface
     */
    private function getScopeConfigMock(): ScopeConfigInterface
    {
        return $this->createMock(ScopeConfigInterface::class);
    }

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @return Context
     */
    private function getContext(ScopeConfigInterface $scopeConfig): Context
    {
        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('getScopeConfig')
            ->willReturn($scopeConfig);
        return $context;
    }

    private function getObjectTest(?Context $context = null)
    {
        if ($context) {
            $args = [
                'context' => $context,
                'remoteAddress' => $this->remoteAddress,
            ];
        } else {
            $args = [
                'remoteAddress' => $this->remoteAddress,
            ];
        }
        return $this->objectManager->getObject(Data::class, $args);
    }

    public function testFormatPrice()
    {
        $price = 1.00;
        $result = $this->helper->formatPrice($price);
        $this->assertEquals('1.00', $result);

        $price = 10.5;
        $result = $this->helper->formatPrice($price);
        $this->assertEquals('10.50', $result);

        $price = 0;
        $result = $this->helper->formatPrice($price);
        $this->assertEquals('0.00', $result);
    }

    public function testIsCCSplitCaptureEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::IS_CC_SPLIT_CAPTURE_ENABLED,
                ScopeInterface::SCOPE_WEBSITE
            )
            ->willReturn('1');

        $result = $this->helper->isCCSplitCaptureEnabled();
        $this->assertEquals('1', $result);
    }

    public function testIsGPaySplitCaptureEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::IS_GPAY_SPLIT_CAPTURE_ENABLED,
                ScopeInterface::SCOPE_WEBSITE
            )
            ->willReturn('1');

        $result = $this->helper->isGPaySplitCaptureEnabled();
        $this->assertEquals('1', $result);
    }

    public function testGetIpAddress()
    {
        $ipAddress = '127.0.0.1';
        $this->remoteAddress->expects($this->once())
            ->method('getRemoteAddress')
            ->with(false)
            ->willReturn($ipAddress);

        $result = $this->helper->getIpAddress();
        $this->assertEquals($ipAddress, $result);
    }

    public function testIsCardknoxGiftcardEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::IS_CARDKNOX_GIFTCARD_ENABLED,
                ScopeInterface::SCOPE_WEBSITE
            )
            ->willReturn('1');

        $result = $this->helper->isCardknoxGiftcardEnabled();
        $this->assertEquals('1', $result);
    }

    public function testCardknoxGiftcardText()
    {
        $text = 'Test Gift Card Text';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::IS_CARDKNOX_GIFTCARD_TEXT,
                ScopeInterface::SCOPE_WEBSITE
            )
            ->willReturn($text);

        $result = $this->helper->cardknoxGiftcardText();
        $this->assertEquals($text, $result);
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

        $result = $this->helper->getConfigValue($key, $storeId);
        $this->assertEquals($value, $result);
    }
}
