<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Controller\Adminhtml\System\Config;

/**
 * Class \Ess\M2ePro\Plugin\Config\Magento\Config\Controller\Adminhtml\System\Config\Edit
 */
class Edit extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    protected function canExecute()
    {
        if ($this->helperFactory->getObject('Module\Maintenance')->isEnabled()) {
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
