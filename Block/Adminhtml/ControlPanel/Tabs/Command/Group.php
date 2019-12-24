<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Command\Group
 */
class Group extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('control_panel/tabs/command/group.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->enabledComponents = $this->getHelper('Component')->getEnabledComponents();

        $this->commands = $this->getHelper('View_ControlPanel_Command')
                            ->parseGeneralCommandsData($this->getControllerName());

        return parent::_beforeToHtml();
    }

    //########################################

    public function getCommandLauncherHtml(array $commandRow, $component = null)
    {
        $href = $commandRow['url'];
        $component && $href = rtrim($commandRow['url'], '/')."/component/{$component}/";

        $target = '';
        $commandRow['new_window'] && $target = 'target="_blank"';

        $onClick = '';
        $commandRow['confirm'] && $onClick = "return confirm('{$commandRow['confirm']}');";
        if (!empty($commandRow['prompt']['text']) && !empty($commandRow['prompt']['var'])) {
            $onClick =  <<<JS
var result = prompt('{$commandRow['prompt']['text']}');
if (result) window.location.href = $(this).getAttribute('href') + '?{$commandRow['prompt']['var']}=' + result;
return false;
JS;
        }

        $title = $commandRow['title'];
        $component && $title = $component;

        return <<<HTML
<a href="{$href}" {$target} onclick="{$onClick}" title="{$commandRow['description']}">{$title}</a>
HTML;
    }

    //########################################
}
