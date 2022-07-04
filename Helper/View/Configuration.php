<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Configuration
{
    public const NICK = 'configuration';

    public const EBAY_SECTION_COMPONENT = 'm2epro_ebay_integration';
    public const AMAZON_SECTION_COMPONENT = 'm2epro_amazon_integration';
    public const WALMART_SECTION_COMPONENT = 'm2epro_walmart_integration';
    public const ADVANCED_SECTION_COMPONENT = 'm2epro_advanced_settings';
    public const ADVANCED_SECTION_WIZARD = 'm2epro_migration_wizard';

    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;
    /** @var \Ess\M2ePro\Helper\Component */
    private $component;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Ess\M2ePro\Helper\Component $component
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->translation = $translation;
        $this->component = $component;
    }

    // ----------------------------------------

    public function getTitle()
    {
        return $this->translation->__('Configuration');
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getComponentsUrl(array $params = []): string
    {
        return $this->urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            array_merge([
                'section' => self::EBAY_SECTION_COMPONENT,
            ], $params)
        );
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getSettingsUrl(array $params = []): string
    {
        return $this->urlBuilder->getUrl("m2epro/{$this->getFirstEnabledComponent()}_settings/index", $params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getLogsClearingUrl(array $params = []): string
    {
        return $this->urlBuilder->getUrl(
            "m2epro/{$this->getFirstEnabledComponent()}_settings/index",
            array_merge([
                'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LOGS_CLEARING,
            ], $params)
        );
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getLicenseUrl(array $params = []): string
    {
        return $this->urlBuilder->getUrl(
            "m2epro/{$this->getFirstEnabledComponent()}_settings/index",
            array_merge([
                'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LICENSE,
            ], $params)
        );
    }

    private function getFirstEnabledComponent(): string
    {
        $components = $this->component->getEnabledComponents();

        return !empty($components) ? $components[0] : \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }
}
