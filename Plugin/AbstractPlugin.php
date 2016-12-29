<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin;

use Ess\M2ePro\Model\Exception;

abstract class AbstractPlugin
{
    protected $helperFactory;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
    }

    //########################################

    protected function execute($name, $interceptor, \Closure $callback, array $arguments = [])
    {
        if (!$this->canExecute()) {
            return empty($arguments) ? $callback() : call_user_func_array($callback, $arguments);
        }

        $processMethod = 'process' . ucfirst($name);

        if (!method_exists($this, $processMethod)) {
            throw new Exception("Method {$processMethod} doesn't exists");
        }

        return $this->{$processMethod}($interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function canExecute()
    {
        return !$this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() &&
               !$this->helperFactory->getObject('Module')->isDisabled() &&
               $this->helperFactory->getObject('Module')->isReadyToWork();
    }

    //########################################
}