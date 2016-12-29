<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    // M2ePro_TRANSLATIONS
    // Configuration

    const NICK  = 'configuration';

    const EBAY_SECTION_COMPONENT     = 'm2epro_ebay_integration';
    const AMAZON_SECTION_COMPONENT   = 'm2epro_amazon_integration';
    const BUY_SECTION_COMPONENT      = 'm2epro_buy_integration';
    const ADVANCED_SECTION_COMPONENT = 'm2epro_advanced_settings';
    const ADVANCED_SECTION_WIZARD    = 'm2epro_migration_wizard';

    protected $urlBuilder;

    //########################################

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
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
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit',array_merge([
            'section' => self::EBAY_SECTION_COMPONENT
        ], $params));
    }

    public function getSettingsUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl('m2epro/ebay_settings/index', $params);
    }

    public function getLogsClearingUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl('m2epro/ebay_settings/index',array_merge([
            'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LOGS_CLEARING
        ], $params));
    }

    public function getLicenseUrl(array $params = [])
    {
        return $this->urlBuilder->getUrl('m2epro/ebay_settings/index',array_merge([
            'active_tab' => \Ess\M2ePro\Block\Adminhtml\Settings\Tabs::TAB_ID_LICENSE
        ], $params));
    }

    //########################################
}