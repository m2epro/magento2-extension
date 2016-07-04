<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento;

class Translate extends \Magento\Framework\Translate
{
    const TRANSLATE_MODE_NORMAL = 0;
    const TRANSLATE_MODE_CUSTOM = 1;

    const TRANSLATE_AREA = 'adminhtml';

    const MODULE_NAME = 'Ess\M2ePro';

    private $translateInline;

    protected $mode = self::TRANSLATE_MODE_CUSTOM;

    //########################################

    public function __construct(
        \Magento\Framework\Translate\Inline $translateInline,
        \Magento\Framework\View\DesignInterface $viewDesign,
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\Module\Dir\Reader $modulesReader,
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\Translate\ResourceInterface $translate,
        \Magento\Framework\Locale\ResolverInterface $locale,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\App\Language\Dictionary $packDictionary
    )
    {
        $this->translateInline = $translateInline;
        parent::__construct(
            $viewDesign,
            $cache,
            $viewFileSystem,
            $moduleList,
            $modulesReader,
            $scopeResolver,
            $translate,
            $locale,
            $appState,
            $filesystem,
            $request,
            $csvParser,
            $packDictionary
        );
    }

    //########################################

    /**
     * @param mixed $mode
     */
    protected function setMode($mode)
    {
        $this->mode = $mode;
    }
    /**
     * @return mixed
     */
    protected function getMode()
    {
        return $this->mode;
    }

    //TODO
    public function init($area = self::TRANSLATE_AREA, $forceReload = false)
    {
        // regular object returned
        if ($this->getMode() == self::TRANSLATE_MODE_NORMAL) {
            return $this->loadData(self::TRANSLATE_AREA, $forceReload);
        }

        $this->setConfig(array(
            'area' => self::TRANSLATE_AREA
        ));

        $this->_translateInline = Mage::getSingleton('core/translate_inline')
            ->isAllowed($area=='adminhtml' ? 'admin' : null);

        if (!$forceReload) {
            if ($this->_canUseCache()) {
                $this->_data = $this->_loadCache();
                if ($this->_data !== false) {
                    return $this;
                }
            }
            Mage::app()->removeCache($this->getCacheId());
        }

        $this->_data = array();

        $modulesConfig = $this->getModulesConfig();

        if (isset($modulesConfig->{self::MODULE_NAME})) {
            $info = $modulesConfig->{self::MODULE_NAME}->asArray();
            $this->_loadModuleTranslation(self::MODULE_NAME, $info['files'], $forceReload);
        }

        if (!$forceReload && $this->_canUseCache()) {
            $this->_saveCache();
        }

        return $this;
    }

    public function getCacheId($forceReload = false)
    {
        $this->_cacheId = self::MODULE_NAME.'_'.parent::getCacheId();
        return $this->_cacheId;
    }

    public function __()
    {
        $args = func_get_args();
        return parent::translate($args);
    }

    //########################################
}