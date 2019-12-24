<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\Additional
 */
class Additional extends Command
{
    private $cookieManager;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        Context $context
    ) {
        $this->cookieManager = $cookieManager;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Memory Limit Test"
     * @description "Memory Limit Test"
     * @confirm "Are you sure?"
     */
    public function testMemoryLimitAction()
    {
        ini_set('display_errors', 1);

        /** @var \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig */
        $cacheConfig = $this->modelFactory->getObject('Config_Manager_Cache');

        if (!$this->getRequest()->getParam('do_test', null)) {
            $html = '';
            if ($value = $cacheConfig->getGroupValue('/control_panel/', 'test_memory_limit')) {
                $value = round($value, 2);
                $html .= "<span>Previous result: {$value} MB</span><br>";
            }

            $url = $this->getUrl('*/*/*', ['action' => 'testMemoryLimit']);

            return <<<HTML
        {$html}
<form action="{$url}" method="get">
    <button name="do_test" value="1" type="submit">Test</button>
</form>
HTML;
        }

        $i = 0;
        $array = [];

        while (1) {
            ($array[] = $array) && ((++$i % 100) == 0)
            && $cacheConfig->setGroupValue(
                '/control_panel/',
                'test_memory_limit',
                memory_get_usage(true) / 1000000
            );
        }
    }

    /**
     * @title "Execution Time Test"
     * @description "Execution Time Test"
     * @new_line
     */
    public function testExecutionTimeAction()
    {
        ini_set('display_errors', 1);
        $seconds = (int)$this->getRequest()->getParam('seconds', null);

        /** @var \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig */
        $cacheConfig = $this->modelFactory->getObject('Config_Manager_Cache');

        $html = '';
        if ($value = $cacheConfig->getGroupValue('/control_panel/', 'test_execution_time')) {
            $html .= "<span>Previous result: {$value}</span><br>";
        }

        if ($seconds) {
            $i = 0;
            while ($i < $seconds) {
                sleep(1);
                ((++$i % 10) == 0) && $cacheConfig->setGroupValue(
                    '/control_panel/',
                    'test_execution_time',
                    "{$i} seconds passed"
                );
                ;
            }

            $html .= "<div>{$seconds} seconds passed successfully!</div><br/>";
        }

        $url = $this->getUrl('*/*/*', ['action' => 'testExecutionTime']);

        return <<<HTML
        {$html}
<form action="{$url}" method="get">
    <input type="text" name="seconds" class="input-text" value="180" style="text-align: right; width: 100px" />
    <button type="submit">Test</button>
</form>
HTML;
    }

    /**
     * @title "Clear Opcode"
     * @description "Clear Opcode (APC and Zend Optcache Extension)"
     */
    public function clearOpcodeAction()
    {
        $messages = [];

        if (!$this->getHelper('Client\Cache')->isApcAvailable() &&
            !$this->getHelper('Client\Cache')->isZendOpcacheAvailable()) {
            $this->getMessageManager()->addError('Opcode extensions are not installed.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
        }

        if ($this->getHelper('Client\Cache')->isApcAvailable()) {
            $messages[] = 'APC opcode';
            apc_clear_cache('system');
        }

        if ($this->getHelper('Client\Cache')->isZendOpcacheAvailable()) {
            $messages[] = 'Zend Optcache';
            opcache_reset();
        }

        $this->getMessageManager()->addSuccess(implode(' and ', $messages) . ' caches are cleared.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
    }

    //########################################
}
