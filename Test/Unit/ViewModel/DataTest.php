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

    public function testIsActiveGooglePayWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_GOOGLEPAY_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->isActiveGooglePay());
    }

    public function testIsActiveGooglePayWhenNull()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_GOOGLEPAY_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertEquals($expectedData, $this->viewModel->isActiveGooglePay());
    }

    public function testIsActiveApplePay()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_APPLEPAY_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->isActiveApplePay());
    }

    public function testIsActiveApplePayWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_APPLEPAY_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->isActiveApplePay());
    }

    public function testIsEnabledReCaptcha()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_ENABLE_GOOGLE_REPCAPTCHA, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledReCaptcha());
    }

    public function testIsEnabledReCaptchaWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::IS_ENABLE_GOOGLE_REPCAPTCHA, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledReCaptcha());
    }

    public function testGetSelectReCaptchaSource()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_CC_SELECT_RECAPTCHA_SOURCE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->getSelectReCaptchaSource());
    }

    public function testGetSelectReCaptchaSourceWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::XPATH_FIELD_CC_SELECT_RECAPTCHA_SOURCE, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->getSelectReCaptchaSource());
    }

    public function testIsEnabledApplyPayOnCartPage()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::APPLEPAY_ENABLE_ON_CARTPAGE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledApplyPayOnCartPage());
    }

    public function testIsEnabledApplyPayOnCartPageWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::APPLEPAY_ENABLE_ON_CARTPAGE, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledApplyPayOnCartPage());
    }

    public function testIsEnabledGooglePayOnCartPage()
    {
        $expectedData = true;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::GOOGLEPAY_ENABLE_ON_CARTPAGE, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledGooglePayOnCartPage());
    }

    public function testIsEnabledGooglePayOnCartPageWhenDisabled()
    {
        $expectedData = false;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Data::GOOGLEPAY_ENABLE_ON_CARTPAGE, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedData, $this->viewModel->isEnabledGooglePayOnCartPage());
    }
}
