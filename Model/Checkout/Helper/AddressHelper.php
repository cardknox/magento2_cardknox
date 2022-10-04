<?php

namespace CardknoxDevelopment\Cardknox\Model\Checkout\Helper;

use Magento\Directory\Model\RegionFactory;

class AddressHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * __construct function
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        RegionFactory $regionFactory
    ) {
        $this->regionFactory = $regionFactory;
        parent::__construct($context);
    }

    /**
     * Get the region ID based on the region code.
     *
     * @param mixed $regionCode - region code
     * @param mixed $countryId  - country code
     * @return int|bool
     */
    public function getRegionIdByCode($regionCode, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            $regionId = $region->loadByCode($regionCode, $countryId)->getId();
            return $regionId;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get the region ID based on the region name
     *
     * @param mixed $regionName - region name
     * @param mixed $countryId  - country code
     * @return string|bool
     */
    public function getRegionCodeByName($regionName, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            $regionCode = $region->loadByName($regionName, $countryId)->getCode();
            return $regionCode;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get the region based on the region code.
     *
     * @param mixed $regionCode - region code
     * @param mixed $countryId  - country code
     * @return int|bool
     */
    public function getRegionByCode($regionCode, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            return $region->loadByCode($regionCode, $countryId);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get the region based on the region name
     *
     * @param mixed $regionName - region name
     * @param mixed $countryId  - country code
     * @return string|bool
     */
    public function getRegionByName($regionName, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            return $region->loadByName($regionName, $countryId);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }
}
