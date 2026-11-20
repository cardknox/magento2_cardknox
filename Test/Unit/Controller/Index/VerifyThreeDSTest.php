<?php

declare(strict_types=1);

namespace CardknoxDevelopment\Cardknox\Test\Unit\Controller\Index;

use CardknoxDevelopment\Cardknox\Controller\Index\VerifyThreeDS;
use CardknoxDevelopment\Cardknox\Gateway\Config\Config;
use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AmountMismatchHandler;
use CardknoxDevelopment\Cardknox\Model\ThreeDSecure\AuthorizedAmountValidator;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerifyThreeDSTest extends TestCase
{
    private const QUOTE_ID = 77;
    private const STORE_ID = 2;
    private const CART_AMOUNT = '25.00';
    private const ORDER_ID = 501;

    /** @var AuthorizedAmountValidator&MockObject */
    private $validator;

    /** @var AmountMismatchHandler&MockObject */
    private $handler;

    /** @var CartManagementInterface&MockObject */
    private $cartManagement;

    /** @var Session&MockObject */
    private $session;

    /** @var Quote&MockObject */
    private $quote;

    /** @var Payment&MockObject */
    private $payment;

    /** @var VerifyThreeDS */
    private $controller;

    /** @var array */
    private $jsonData = [];

    protected function setUp(): void
    {
        $this->validator = $this->createMock(AuthorizedAmountValidator::class);
        $this->handler = $this->createMock(AmountMismatchHandler::class);
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->payment = $this->createPaymentMock();
        $this->quote = $this->createQuoteMock();
        $this->session = $this->createSessionMock();

        $this->controller = (new ObjectManager($this))->getObject(
            VerifyThreeDS::class,
            [
                'context' => $this->createContextMock(),
                'resultJsonFactory' => $this->createJsonFactoryMock(),
                'curl' => $this->createCurlMock(),
                'orderFactory' => $this->createOrderFactoryMock(),
                'checkoutSession' => $this->session,
                'orderRepository' => $this->createMock(OrderRepositoryInterface::class),
                'config' => $this->createConfigMock(),
                'productMetadata' => $this->createMock(ProductMetadataInterface::class),
                'cartManagementInterface' => $this->cartManagement,
                'logger' => $this->createMock(Logger::class),
                'transactionBuilder' => $this->createTransactionBuilderMock(),
                'authorizedAmountValidator' => $this->validator,
                'amountMismatchHandler' => $this->handler,
            ]
        );
    }

    public function testItPlacesTheOrderWhenTheApprovedAmountMatchesTheCartTotal(): void
    {
        $this->validator->method('isMatch')->willReturn(true);
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with(self::QUOTE_ID)
            ->willReturn(self::ORDER_ID);

        $this->controller->execute();

        $this->assertTrue($this->jsonData['success']);
    }

    public function testItDoesNotPlaceTheOrderWhenTheApprovedAmountDiffersFromTheCartTotal(): void
    {
        $this->validator->method('isMatch')->willReturn(false);
        $this->cartManagement->expects($this->never())->method('placeOrder');

        $this->controller->execute();
    }

    public function testItHandsTheMismatchToTheHandlerWithCartAmountQuoteIdAndStoreId(): void
    {
        $this->validator->method('isMatch')->willReturn(false);
        $this->validator->method('getCartAmount')->with($this->quote)->willReturn(self::CART_AMOUNT);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(static fn (array $response): bool => $response['xRefNum'] === '9001'),
                self::CART_AMOUNT,
                self::QUOTE_ID,
                self::STORE_ID
            );

        $this->controller->execute();
    }

    public function testItReturnsSuccessFalseWithARetryMessageAndCartRedirectOnMismatch(): void
    {
        $this->validator->method('isMatch')->willReturn(false);

        $this->controller->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertStringContainsString('place the order again', (string) $this->jsonData['message']);
        $this->assertSame('url/checkout/cart', $this->jsonData['redirect']);
    }

    public function testItDoesNotSetTheSkipValidationFlagOrModifyTheQuoteOnMismatch(): void
    {
        $this->validator->method('isMatch')->willReturn(false);
        $this->session->expects($this->never())->method('setSkipValidation');
        $this->quote->expects($this->never())->method('setReservedOrderId');
        $this->quote->expects($this->never())->method('save');
        $this->payment->expects($this->never())->method('save');

        $this->controller->execute();
    }

    public function testItDoesNotCallTheMismatchHandlerWhenAmountsMatch(): void
    {
        $this->validator->method('isMatch')->willReturn(true);
        $this->cartManagement->method('placeOrder')->willReturn(self::ORDER_ID);
        $this->handler->expects($this->never())->method('handle');

        $this->controller->execute();
    }

    private function createPaymentMock(): Payment&MockObject
    {
        return $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setAdditionalInformation', 'save'])
            ->addMethods(['setTransactionId', 'setLastTransId', 'setIsTransactionClosed'])
            ->getMock();
    }

    private function createQuoteMock(): Quote&MockObject
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $quote = $this->createMock(Quote::class);
        $quote->method('getStore')->willReturn($store);
        $quote->method('getStoreId')->willReturn(self::STORE_ID);
        $quote->method('getId')->willReturn(self::QUOTE_ID);
        $quote->method('getPayment')->willReturn($this->payment);

        return $quote;
    }

    private function createSessionMock(): Session&MockObject
    {
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods([
                'setSkipValidation',
                'setLastSuccessQuoteId',
                'setLastQuoteId',
                'setLastOrderId',
                'setLastRealOrderId',
                'setLastOrderStatus',
            ])
            ->getMock();
        $session->method('getQuote')->willReturn($this->quote);
        $session->method('setLastOrderId')->willReturnSelf();
        $session->method('setLastRealOrderId')->willReturnSelf();

        return $session;
    }

    private function createContextMock(): Context&MockObject
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->willReturn(['xCardNum' => '4111111111111111']);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(static fn ($path): string => 'url/' . $path);
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getMessageManager')->willReturn($this->createMock(ManagerInterface::class));
        $context->method('getUrl')->willReturn($url);

        return $context;
    }

    private function createJsonFactoryMock(): JsonFactory&MockObject
    {
        $json = $this->createMock(Json::class);
        $json->method('setData')->willReturnCallback(function (array $data) use ($json) {
            $this->jsonData = $data;

            return $json;
        });
        $factory = $this->createMock(JsonFactory::class);
        $factory->method('create')->willReturn($json);

        return $factory;
    }

    private function createCurlMock(): Curl&MockObject
    {
        $curl = $this->createMock(Curl::class);
        $curl->method('getBody')->willReturn(
            'xResult=A&xInvoice=000000123&xRefNum=9001&xAuthAmount=25.00'
        );

        return $curl;
    }

    private function createOrderFactoryMock(): OrderFactory&MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('load')->willReturnSelf();
        $order->method('getPayment')->willReturn($this->createMock(\Magento\Sales\Model\Order\Payment::class));
        $factory = $this->createMock(OrderFactory::class);
        $factory->method('create')->willReturn($order);

        return $factory;
    }

    private function createConfigMock(): Config&MockObject
    {
        $config = $this->createMock(Config::class);
        $config->method('getThreeDSVerifyUrl')->willReturn('https://example.test/verify');
        $config->method('getValue')->willReturnMap([
            ['cardknox_transaction_key', self::STORE_ID, 'key'],
            ['payment_action', self::STORE_ID, 'authorize'],
        ]);

        return $config;
    }

    private function createTransactionBuilderMock(): BuilderInterface&MockObject
    {
        $builder = $this->createMock(BuilderInterface::class);
        $builder->method('setPayment')->willReturnSelf();
        $builder->method('setOrder')->willReturnSelf();
        $builder->method('setTransactionId')->willReturnSelf();
        $builder->method('setFailSafe')->willReturnSelf();
        $builder->method('build')->willReturn($this->createMock(Transaction::class));

        return $builder;
    }
}
