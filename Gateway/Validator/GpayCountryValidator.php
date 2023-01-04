<?php

namespace CardknoxDevelopment\Cardknox\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;

class GpayCountryValidator extends AbstractValidator
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

        parent::__construct($resultFactory);
    }

    /**
     * Validate country
     *
     * @param array $validationSubject
     * @return bool
     * @throws NotFoundException
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $storeId = $validationSubject['storeId'];
        $allowspecific = $this->scopeConfig->getValue(
            'payment/cardknox_google_pay/allowspecific',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ((int)$allowspecific === 1) {
            $availableCountries = explode(
                ',',
                $this->scopeConfig->getValue(
                    'payment/cardknox_google_pay/specificcountry',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ) ?? ''
            );

            if (!in_array($validationSubject['country'], $availableCountries)) {
                $isValid =  false;
            }
        }
      
        return $this->createResult($isValid);
    }
}
