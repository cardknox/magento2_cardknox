<?php

namespace CardknoxDevelopment\Cardknox\Controller\Giftcard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CardknoxDevelopment\Cardknox\Helper\Giftcard;
use CardknoxDevelopment\Cardknox\Helper\Data as DataHelper;

abstract class AbstractGiftcardAction extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Giftcard
     */
    protected $giftcardHelper;

    /**
     * @var DataHelper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Giftcard $giftcardHelper
     * @param DataHelper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Giftcard $giftcardHelper,
        DataHelper $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->giftcardHelper = $giftcardHelper;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Check if giftcard is enabled and return error response if not
     *
     * @return \Magento\Framework\Controller\Result\Json|null
     */
    protected function validateGiftcardEnabled()
    {
        if (!$this->helper->isCardknoxGiftcardEnabled()) {
            return $this->createJsonResponse(false, __('Please enable Sola Gift.'));
        }
        return null;
    }

    /**
     * Get and validate gift card code from request
     *
     * @return string|null
     */
    protected function getGiftCardCode()
    {
        return $this->getRequest()->getParam('giftcard_code');
    }

    /**
     * Validate gift card code and return error response if missing
     *
     * @param string|null $giftCardCode
     * @return \Magento\Framework\Controller\Result\Json|null
     */
    protected function validateGiftCardCode($giftCardCode)
    {
        if (!$giftCardCode) {
            return $this->createJsonResponse(false, __('Gift Card code is required.'));
        }
        return null;
    }

    /**
     * Create JSON response
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function createJsonResponse($success, $message, array $data = [])
    {
        $responseData = array_merge(['success' => $success, 'message' => $message], $data);
        return $this->resultJsonFactory->create()->setData($responseData);
    }
}
