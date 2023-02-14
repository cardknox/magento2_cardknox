<?php
namespace CardknoxDevelopment\Cardknox\Test\Unit\ViewModel;

use Magento\Framework\Module\Output\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Store\Model\ScopeInterface;
use CardknoxDevelopment\Cardknox\ViewModel\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DataTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $viewModel;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->viewModel = new Data($this->scopeConfig);
    }

    public function testIsActiveGooglePay()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_GOOGLEPAY_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->isActiveGooglePay());
    }
}
