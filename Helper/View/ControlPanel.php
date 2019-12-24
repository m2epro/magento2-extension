<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

use Ess\M2ePro\Helper\Factory;

/**
 * Class \Ess\M2ePro\Helper\View\ControlPanel
 */
class ControlPanel extends \Ess\M2ePro\Helper\AbstractHelper
{
    // M2ePro_TRANSLATIONS
    // Control Panel (M2E Pro)

    const NICK                 = 'control_panel';

    const TAB_OVERVIEW         = 'overview';
    const TAB_INSPECTION       = 'inspection';
    const TAB_VERSIONS_HISTORY = 'versions_history';
    const TAB_DATABASE         = 'database';
    const TAB_TOOLS_GENERAL    = 'tools_general';
    const TAB_TOOLS_MODULE     = 'tools_module';
    const TAB_DEBUG            = 'debug';

    private $backendUrlBuilder;

    //########################################

    public function __construct(
        \Magento\Backend\Model\Url $backendUrlBuilder,
        Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->backendUrlBuilder = $backendUrlBuilder;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Control Panel (M2E Pro)');
    }

    //########################################

    public function getPageUrl(array $params = [])
    {
        return $this->backendUrlBuilder->getUrl($this->getPageRoute(), $params);
    }

    public function getPageRoute()
    {
        return 'm2epro/controlPanel/index';
    }

    //########################################

    public function getPageOwerviewTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_OVERVIEW]));
    }

    public function getPageInspectionTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_INSPECTION]));
    }

    public function getPageDatabaseTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_DATABASE]));
    }

    public function getPageVersionsHistoryTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_VERSIONS_HISTORY]));
    }

    public function getPageToolsTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_TOOLS_GENERAL]));
    }

    public function getPageModuleTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_TOOLS_MODULE]));
    }

    public function getPageDebugTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_DEBUG]));
    }

    //########################################
}
