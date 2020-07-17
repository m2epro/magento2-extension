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
