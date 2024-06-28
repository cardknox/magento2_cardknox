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
                'context' => $context
            ];
        } else {
            $args = [];
        }
        return $this->objectManager->getObject(Data::class, $args);
    }

    public function testFormatPrice()
    {
        $price = 1.00;
        $this->helper->formatPrice($price);
    }

    public function testIsCCSplitCaptureEnabled()
    {
        $scopeConfig = $this->getScopeConfigMock();
        $scopeConfig->expects($this->once())
            ->method('getValue');
        $context = $this->getContext($scopeConfig);
        $subject = $this->getObjectTest($context);
        $subject->isCCSplitCaptureEnabled();
    }

    public function testIsGPaySplitCaptureEnabled()
    {
        $scopeConfig = $this->getScopeConfigMock();
        $scopeConfig->expects($this->once())
            ->method('getValue');
        $context = $this->getContext($scopeConfig);
        $subject = $this->getObjectTest($context);
        $subject->isGPaySplitCaptureEnabled();
    }
}
