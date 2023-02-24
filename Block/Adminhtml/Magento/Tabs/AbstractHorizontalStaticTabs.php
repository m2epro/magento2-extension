<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Tabs;

abstract class AbstractHorizontalStaticTabs extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var array<string, array{content: string, url:string, title: string}> */
    private $tabs = [];
    /** @var string[]  */
    private $registeredCss = [];
    /** @var string|null */
    private $activeTabId = null;
    /** @var string */
    protected $_template = 'Ess_M2ePro::magento/tabs/horizontal_static.phtml';

    /**
     * @return void
     */
    abstract protected function init(): void;

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $this->init();

        return parent::_prepareLayout();
    }

    /**
     * @param string $tabId
     * @param string $content
     * @param string $url
     * @param string|null $title
     *
     * @return void
     */
    protected function addTab(
        string $tabId,
        string $content,
        string $url,
        string $title = null
    ): void {
        $this->tabs[$tabId] = [
            'content' => $content,
            'url' => $url,
            'title' => $title ?? $content,
        ];
    }

    /**
     * @return array<int, array{content: string, url:string, title: string, is_active: bool}>
     */
    public function getTabs(): array
    {
        $resultTabs = [];
        foreach ($this->tabs as $tabId => $val) {
            $resultTabs[] = array_merge(
                $val,
                ['is_active' => $this->isActiveTab($tabId)]
            );
        }

        return $resultTabs;
    }

    /**
     * @param string $tabId
     * @return bool
     */
    private function isActiveTab(string $tabId): bool
    {
        return $this->activeTabId === $tabId;
    }

    /**
     * @param string $tabId
     *
     * @return void
     */
    protected function setActiveTabId(string $tabId): void
    {
        $this->activeTabId = $tabId;
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        foreach ($this->registeredCss as $tabId => $css) {
            if ($this->isActiveTab($tabId)) {
                $this->addCss($css);
                break;
            }
        }

        return parent::_toHtml();
    }

    /**
     * @param string $styles
     * @return void
     */
    protected function addCss(string $styles): void
    {
        $this->css->add('.m2epro-tabs-horizontal-static{ ' . $styles . ' }');
    }

    /**
     * @param string $tabId
     * @param string $css
     *
     * @return void
     */
    protected function registerCssForTab(string $tabId, string $css): void
    {
        $this->registeredCss[$tabId] = $css;
    }
}
