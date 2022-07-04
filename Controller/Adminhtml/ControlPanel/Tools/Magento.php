<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Magento extends Command
{
    protected $fullModuleList;
    protected $moduleList;
    protected $packageInfo;
    /** @var \Ess\M2ePro\Helper\Magento\Plugin */
    protected $magentoPluginHelper;
    /** @var \Ess\M2ePro\Helper\Client\Cache */
    private $clientCacheHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Helper\Client\Cache $clientCacheHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        Context $context,
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Ess\M2ePro\Helper\Magento\Plugin $magentoPluginHelper
    ) {
        parent::__construct($controlPanelHelper, $context);
        $this->fullModuleList = $fullModuleList;
        $this->moduleList = $moduleList;
        $this->packageInfo = $packageInfo;
        $this->magentoPluginHelper = $magentoPluginHelper;
        $this->clientCacheHelper = $clientCacheHelper;
        $this->magentoHelper = $magentoHelper;
    }

    /**
     * @title "Show Event Observers"
     * @description "Show Event Observers"
     */
    public function showEventObserversAction()
    {
        $eventObservers = $this->magentoHelper->getAllEventObservers();

        $html = $this->getStyleHtml();

        $html .= <<<HTML

<h2 style="margin: 20px 0 0 10px">Event Observers</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0">
    <tr>
        <th style="width: 50px">Area</th>
        <th style="width: 500px">Event</th>
        <th style="width: 500px">Observer</th>
    </tr>

HTML;

        foreach ($eventObservers as $area => $areaEvents) {
            if (empty($areaEvents)) {
                continue;
            }

            $areaRowSpan = count($areaEvents, COUNT_RECURSIVE) - count($areaEvents);

            $html .= '<tr>';
            $html .= '<td valign="top" rowspan="' . $areaRowSpan . '">' . $area . '</td>';

            foreach ($areaEvents as $eventName => $eventData) {
                if (empty($eventData)) {
                    continue;
                }

                $eventRowSpan = count($eventData);

                $html .= '<td rowspan="' . $eventRowSpan . '">' . $eventName . '</td>';

                $isFirstObserver = true;
                foreach ($eventData as $observer) {
                    if (!$isFirstObserver) {
                        $html .= '<tr>';
                    }

                    $html .= '<td>' . $observer . '</td>';
                    $html .= '</tr>';

                    $isFirstObserver = false;
                }
            }
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * @title "Show Installed Modules"
     * @description "Show Installed Modules"
     */
    public function showInstalledModulesAction()
    {
        $html = $this->getStyleHtml();

        $html .= <<<HTML

<h2 style="margin: 20px 0 0 10px">Installed Modules
    <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0">
    <tr>
        <th style="width: 350px">Module</th>
        <th style="width: 100px">Status</th>
        <th style="width: 100px">Composer Version</th>
        <th style="width: 100px">Setup Version</th>
    </tr>

HTML;
        $fullModulesList = $this->fullModuleList->getAll();
        ksort($fullModulesList);

        foreach ($fullModulesList as $module) {
            $status = $this->moduleList->has($module['name'])
                ? '<span style="color: forestgreen">Enabled</span>'
                : '<span style="color: orangered">Disabled</span>';

            $html .= <<<HTML
<tr>
    <td>{$module['name']}</td>
    <td>{$status}</td>
    <td>{$this->packageInfo->getVersion($module['name'])}</td>
    <td>{$module['setup_version']}</td>
</tr>
HTML;
        }

        $html .= '</table>';

        return str_replace('#count#', count($fullModulesList), $html);
    }

    /**
     * @title "Show Plugins (Interceptors) List"
     * @description "Show Plugins (Interceptors) List"
     */
    public function showPluginsListAction()
    {
        $html = $this->getStyleHtml();

        $html .= <<<HTML

<h2 style="margin: 20px 0 0 10px">Magento Interceptors
    <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0">
    <tr>
        <th style="width: 200px">Target Model</th>
        <th style="width: 200px">Plugin Model</th>
        <th style="width: 100px">Status</th>
        <th style="width: 100px">Methods</th>
    </tr>

HTML;
        $fullPluginsList = $this->magentoPluginHelper->getAll();
        ksort($fullPluginsList);

        foreach ($fullPluginsList as $targetModel => $pluginsList) {
            $rowSpan = count($pluginsList);

            foreach ($pluginsList as $pluginIndex => $plugin) {
                $methods = implode(', ', $plugin['methods']);
                $status = $plugin['disabled'] ? '<span style="color: orangered">Disabled</span>'
                    : '<span style="color: forestgreen">Enabled</span>';

                if ($pluginIndex == 0) {
                    $html .= <<<HTML
<tr>
    <td rowspan="{$rowSpan}">{$targetModel}</td>
    <td>{$plugin['class']}</td>
    <td>{$status}</td>
    <td>{$methods}</td>
</tr>
HTML;
                } else {
                    $html .= <<<HTML
<tr>
    <td>{$plugin['class']}</td>
    <td>{$status}</td>
    <td>{$methods}</td>
</tr>
HTML;
                }
            }
        }

        $html .= '</table>';

        return str_replace('#count#', count($fullPluginsList), $html);
    }

    /**
     * @title "Clear Cache"
     * @description "Clear magento cache"
     * @confirm "Are you sure?"
     */
    public function clearMagentoCacheAction()
    {
        $this->magentoHelper->clearCache();
        $this->getMessageManager()->addSuccess('Magento cache was cleared.');
        $this->_redirect($this->controlPanelHelper->getPageModuleTabUrl());
    }

    /**
     * @title "Clear Opcode"
     * @description "Clear Opcode (APC and Zend Optcache Extension)"
     */
    public function clearOpcodeAction()
    {
        $messages = [];

        if (
            !$this->clientCacheHelper->isApcAvailable()
            && !$this->clientCacheHelper->isZendOpcacheAvailable()
        ) {
            $this->getMessageManager()->addError('Opcode extensions are not installed.');

            return $this->_redirect($this->controlPanelHelper->getPageModuleTabUrl());
        }

        if ($this->clientCacheHelper->isApcAvailable()) {
            $messages[] = 'APC opcode';
            apc_clear_cache('system');
        }

        if ($this->clientCacheHelper->isZendOpcacheAvailable()) {
            $messages[] = 'Zend Optcache';
            opcache_reset();
        }

        $this->getMessageManager()->addSuccess(implode(' and ', $messages) . ' caches are cleared.');

        return $this->_redirect($this->controlPanelHelper->getPageModuleTabUrl());
    }

    // ----------------------------------------

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->controlPanelHelper->getPageModuleTabUrl();

        return <<<HTML
<h2 style="margin: 20px 0 0 10px">
    {$messageText} <span style="color: grey; font-size: 10px;">
    <a href="{$backUrl}">[back]</a>
</h2>
HTML;
    }
}
