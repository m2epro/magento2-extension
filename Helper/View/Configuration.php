<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

/**
 * Class \Ess\M2ePro\Helper\View\Configuration
 */
class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'configuration';

    const EBAY_SECTION_COMPONENT     = 'm2epro_ebay_integration';
    const AMAZON_SECTION_COMPONENT   = 'm2epro_amazon_integration';
    const WALMART_SECTION_COMPONENT  = 'm2epro_walmart_integration';
    const ADVANCED_SECTION_COMPONENT = 'm2epro_advanced_settings';
    const ADVANCED_SECTION_WIZARD    = 'm2epro_migration_wizard';

    protected $urlBuilder;

    //########################################

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Configuration');
    }

    //########################################

    public function getComponentsUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit', array_merge([
            'section' => self::EBAY_SECTION_COMPONENT
        ], $params));
    }

    public function getSettingsUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl("m2epro/{$this->getFirstEnabledComponent()}_settings/index", $params);
    }

    public function getLogsClearingUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl("m2epro/{$this->getFirstEnabledComponent()}_settings/index", array_merge([
            'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LOGS_CLEARING
        ], $params));
    }

    public function getLicenseUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl("m2epro/{$this->getFirstEnabledComponent()}_settings/index", array_merge([
            'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LICENSE
        ], $params));
    }

    private function getFirstEnabledComponent()
    {
        $components = $this->getHelper('Component')->getEnabledComponents();

        return !empty($components) ? $components[0] : \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################
}
