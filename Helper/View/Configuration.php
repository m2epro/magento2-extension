<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Configuration
{
    public const NICK = 'configuration';

    public const MODULE_AND_CHANNELS_SECTION_COMPONENT             = 'm2epro_module_and_channels';
    public const INTERFACE_AND_MAGENTO_INVENTORY_SECTION_COMPONENT = 'm2epro_interface_and_magento_inventory';
    public const LOGS_CLEARING_SECTION_COMPONENT                   = 'm2epro_logs_clearing';
    public const EXTENSION_KEY_SECTION_COMPONENT                   = 'm2epro_extension_key';
    public const MIGRATION_SECTION_COMPONENT                       = 'm2epro_migration_from_magento1';
    public const MIGRATION_SECTION_WIZARD                          = 'm2epro_migration_wizard';

    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;

    /**
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getLicenseUrl(array $params = []): string
    {
        return $this->urlBuilder->getUrl(
            "adminhtml/system_config/edit",
            array_merge(
                [
                    'section' => \Ess\M2ePro\Block\Adminhtml\System\Config\Sections::SECTION_ID_LICENSE,
                ],
                $params
            )
        );
    }
}
