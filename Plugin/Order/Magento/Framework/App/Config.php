<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento\Framework\App;

class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    protected $mutableConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Config\Mutable $mutableConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->mutableConfig = $mutableConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function canExecute()
    {
        if (!$this->helperFactory->getObject('Data\GlobalData')->getValue('use_mutable_config') ||
            !$this->mutableConfig->isCanBeUsed())
        {
            return false;
        }

        return parent::canExecute();
    }

    //########################################

    public function aroundGetValue($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getValue', $interceptor, $callback, $arguments);
    }

    protected function processGetValue($interceptor, \Closure $callback, array $arguments)
    {
        $path      = isset($arguments[0]) ? $arguments[0] : NULL;
        $scope     = isset($arguments[1]) ? $arguments[1] : NULL;
        $scopeCode = isset($arguments[2]) ? $arguments[2] : NULL;

        if (!is_string($path) || !is_string($scope)){
            return $callback(...$arguments);
        }

        $overriddenValue = $this->mutableConfig->getValue($path, $scope, $scopeCode);
        if (!is_null($overriddenValue)) {
            return $overriddenValue;
        }

        return $callback(...$arguments);
    }

    //########################################
}