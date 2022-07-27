<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class ControlPanel
{
    public const NICK = 'control_panel';

    public const TAB_OVERVIEW = 'overview';
    public const TAB_INSPECTION = 'inspection';
    public const TAB_DATABASE = 'database';
    public const TAB_TOOLS_MODULE = 'tools_module';
    public const TAB_CRON = 'cron';
    public const TAB_DEBUG = 'debug';

    /** @var \Magento\Backend\Model\Url */
    private $backendUrlBuilder;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;

    /**
     * @param \Ess\M2ePro\Helper\Module\Translation $translation
     * @param \Magento\Backend\Model\Url $backendUrlBuilder
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Magento\Backend\Model\Url $backendUrlBuilder
    ) {
        $this->backendUrlBuilder = $backendUrlBuilder;
        $this->translation = $translation;
    }

    // ----------------------------------------

    /**
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function getTitle()
    {
        return $this->translation->__('Control Panel (M2E Pro)');
    }

    // ----------------------------------------

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageUrl(array $params = []): string
    {
        return $this->backendUrlBuilder->getUrl($this->getPageRoute(), $params);
    }

    /**
     * @return string
     */
    public function getPageRoute(): string
    {
        return 'm2epro/controlPanel/index';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageOwerviewTabUrl(array $params = []): string
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_OVERVIEW]));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageInspectionTabUrl(array $params = []): string
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_INSPECTION]));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageDatabaseTabUrl(array $params = []): string
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_DATABASE]));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageModuleTabUrl(array $params = []): string
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_TOOLS_MODULE]));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPageDebugTabUrl(array $params = []): string
    {
        return $this->getPageUrl(array_merge($params, ['tab' => self::TAB_DEBUG]));
    }
}
