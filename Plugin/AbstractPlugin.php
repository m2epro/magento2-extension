<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin;

use Ess\M2ePro\Model\Exception;

/**
 * Class \Ess\M2ePro\Plugin\AbstractPlugin
 */
abstract class AbstractPlugin
{
    protected $helperFactory;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
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
        return $this->helperFactory->getObject('Magento')->isInstalled() &&
               !$this->helperFactory->getObject('Module\Maintenance')->isEnabled() &&
               !$this->helperFactory->getObject('Module')->isDisabled() &&
               $this->helperFactory->getObject('Module')->isReadyToWork();
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################
}
