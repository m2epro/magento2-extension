<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements\Renderer;

/**
 * @method \Ess\M2ePro\Model\Requirements\Checks\ExecutionTime getCheckObject()
 */
class ExecutionTime extends AbstractRenderer
{
    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Max Execution Time');
    }

    // ---------------------------------------

    public function getMin()
    {
        return <<<HTML
<span style="color: grey;">
      <span>{$this->getCheckObject()->getMin()}</span>&nbsp;/
      <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('recommended')}</span>&nbsp;
      <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('measure')}</span>
</span>
HTML;
    }

    public function getReal()
    {
        $color = $this->getCheckObject()->isMeet() ? 'green' : 'red';

        if ($this->getCheckObject()->getReal() === null) {
            $value = $this->getHelper('Module\Translation')->__('unknown');
            $html = <<<HTML
<span style="color: {$color};">
    <span>{$value}</span>&nbsp;
</span>
HTML;
        } elseif ($this->getCheckObject()->getReal() <= 0) {
            $value = $this->getHelper('Module\Translation')->__('unlimited');
            $html = <<<HTML
<span style="color: {$color};">
    <span>{$value}</span>&nbsp;
</span>
HTML;
        } else {
            $html = <<<HTML
<span style="color: {$color};">
    <span>{$this->getCheckObject()->getReal()}</span>&nbsp;
    <span>{$this->getCheckObject()->getReader()->getExecutionTimeData('measure')}</span>
</span>
HTML;
        }

        if ($this->getHelper('Client')->isPhpApiFastCgi()) {
            $id = strtolower(__CLASS__);
            $notice = $this->getHelper('Module\Translation')->__(
                'PHP is running using <b>fast CGI</b> Module on your web Server.
                 It has its own Settings that override max_execution_time in php.ini or .htaccess.'
            );

            $html .= <<<HTML
<div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://" style="margin-left: 0; top: -5px;"></a>
    <div class="admin__field-tooltip-content" style="">
        {$notice}
    </div>
</div>
HTML;
        }

        return $html;
    }

    //########################################
}
