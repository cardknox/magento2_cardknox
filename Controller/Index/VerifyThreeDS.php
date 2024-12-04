<?php
namespace CardknoxDevelopment\Cardknox\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CardknoxDevelopment\Cardknox\Model\Checkout\Helper\AddressHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Request\InvalidRequestException;

class VerifyThreeDS extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var AddressHelper
     */
    protected $addressHelper;

    /**
     * Login popup function
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AddressHelper $addressHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AddressHelper $addressHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->addressHelper = $addressHelper;
    }

    /**
     * Get country region function
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $posts = $this->getRequest()->getPosts();
        echo "<pre>";
        print_r($posts);
        exit();
        $countryCode = $this->getRequest()->getPost('country_id');
        $regionCodeOrName= $this->getRequest()->getPost('region');
        $region = [];
        $response = ['region' => $region];
        if (!empty($countryCode) && !empty($regionCodeOrName)) {
            if (!in_array($countryCode, ['US', 'CA', 'AU'])) {
                $region = $this->addressHelper->getRegionByName($regionCodeOrName, $countryCode);
            } else {
                $region = $this->addressHelper->getRegionByCode($regionCodeOrName, $countryCode);
            }
            $response = [
                'region' => $region->getData()
            ];
            
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
