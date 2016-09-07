<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure;

class Data extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################
    
    protected function canExecute()
    {
        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled()) {
            return true;
        }
        
        if ($this->helperFactory->getObject('Module\Maintenance\Debug')->isEnabled() &&
            !$this->helperFactory->getObject('Module\Maintenance\Debug')->isOwner()) {
            return true;
        }
        
        return false;
    }

    public function aroundGet($interceptor, \Closure $callback)
    {
        return $this->execute('get', $interceptor, $callback);
    }

    // ---------------------------------------

    protected function processGet($interceptor, \Closure $callback)
    {
        $result = $callback();

        unset($result['tabs']['m2epro']);
        unset($result['sections']['ebay_integration']);
        unset($result['sections']['amazon_integration']);
        unset($result['sections']['buy_integration']);

        return $result;
    }

    //########################################
}