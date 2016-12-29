<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\FunctionGetArgsFix;

class Translation extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    protected function canExecute()
    {
        return true;
    }

    public function around__($interceptor, \Closure $callback, ...$args)
    {
        return $this->execute('__', $interceptor, $callback, $args);
    }

    // ---------------------------------------

    /**
     * If someone will use a Plugin for Magento Block\Helper, then Magento will generate a lot of
     * Interceptors for any final class and our method __() will not work as it does not have any arguments in
     * method signature (arguments are fetching by function func_get_args) and Interceptor will call
     * the parent method as following: return parent::__();
     *
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return string
     */
    protected function process__($interceptor, \Closure $callback, array $arguments = [])
    {
        return $this->helperFactory->getObject('Module\Translation')->translate($arguments);
    }

    //########################################
}