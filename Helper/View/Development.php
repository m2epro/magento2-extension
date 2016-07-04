<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Development extends \Ess\M2ePro\Helper\AbstractHelper
{
    // M2ePro_TRANSLATIONS
    // Control Panel (M2E Pro)

    const NICK            = 'development';

    const TAB_SUMMARY     = 'summary';
    const TAB_ABOUT       = 'about';
    const TAB_INSPECTION  = 'inspection';
    const TAB_DATABASE    = 'database';
    const TAB_TOOLS       = 'tools';
    const TAB_MODULE      = 'module';
    const TAB_DEBUG       = 'debug';
    const TAB_BUILD       = 'build';

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Control Panel (M2E Pro)');
    }

    //########################################

    public function getPageUrl(array $params = [])
    {
        return $this->_urlBuilder->getUrl($this->getPageRoute(), $params);
    }

    public function getPageRoute()
    {
        return 'M2ePro/development/index';
    }

    //########################################

    public function getPageAboutTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_ABOUT]));
    }

    public function getPageInspectionTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_INSPECTION]));
    }

    public function getPageDatabaseTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_DATABASE]));
    }

    public function getPageToolsTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_TOOLS]));
    }

    public function getPageModuleTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_MODULE]));
    }

    public function getPageDebugTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_DEBUG]));
    }

    public function getPageBuildTabUrl(array $params = [])
    {
        return $this->getPageUrl(array_merge($params,['tab' => self::TAB_BUILD]));
    }

    //########################################
}