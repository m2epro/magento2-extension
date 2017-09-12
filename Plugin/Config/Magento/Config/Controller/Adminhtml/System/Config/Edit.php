<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Controller\Adminhtml\System\Config;

class Edit extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    protected function canExecute()
    {
        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled()) {
            return false;
        }

        if ($this->helperFactory->getObject('Module\Maintenance\Debug')->isEnabled() &&
            !$this->helperFactory->getObject('Module\Maintenance\Debug')->isOwner()) {

            return false;
        }

        return true;
    }

    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);

        if ($result instanceof \Magento\Backend\Model\View\Result\Redirect) {
            return $result;
        }

        $result->getConfig()->addPageAsset('Ess_M2ePro::css/help_block.css');
        $result->getConfig()->addPageAsset('Ess_M2ePro::css/system/config.css');

        return $result;
    }

    //########################################
}