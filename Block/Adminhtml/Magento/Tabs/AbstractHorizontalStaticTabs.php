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
    /** @var string[] */
    private $registeredCss = [];
    /** @var string */
    private $commonCssForTabsContainer = '';
    /** @var string|null */
    private $activeTabId = null;
    /** @var string */
    protected $_template = 'Ess_M2ePro::magento/tabs/horizontal_static.phtml';

    abstract protected function init(): void;

    protected function _prepareLayout()
    {
        $this->init();

        return parent::_prepareLayout();
    }

    protected function addTab(
        string $tabId,
        string $content,
        string $url,
        ?string $title = null
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

    private function isActiveTab(string $tabId): bool
    {
        return $this->activeTabId === $tabId;
    }

    public function setActiveTabId(string $tabId): void
    {
        $this->activeTabId = $tabId;
    }

    protected function _toHtml()
    {
        $styles = $this->commonCssForTabsContainer;
        foreach ($this->registeredCss as $tabId => $tabCss) {
            if ($this->isActiveTab($tabId)) {
                $styles .= $tabCss;
                break;
            }
        }

        if (!empty($styles)) {
            $this->css->add('.m2epro-tabs-horizontal-static{ ' . $styles . ' }');
        }

        return parent::_toHtml();
    }

    protected function registerCssForTab(string $tabId, string $css): void
    {
        $this->registeredCss[$tabId] = $css;
    }

    protected function addCssForTabsContainer(string $css): void
    {
        $this->commonCssForTabsContainer = $css;
    }
}
