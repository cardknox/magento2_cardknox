<?php
namespace CardknoxDevelopment\Cardknox\Model\Config\Backend\Source;

class ThreeDSecureEnvironment implements \Magento\Framework\Data\OptionSourceInterface
{
    private const THREE_DS_ENVIRONMENT_STAGING = 'staging';
    private const THREE_DS_ENVIRONMENT_PRODUCTION = 'production';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::THREE_DS_ENVIRONMENT_STAGING,
                'label' => 'Staging',
            ],
            [
                'value' => self::THREE_DS_ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}
