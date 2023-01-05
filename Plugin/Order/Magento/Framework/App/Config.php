<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento\Framework\App;

class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\Magento\Config\Mutable */
    private $mutableConfig;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;


    public function __construct(
        \Ess\M2ePro\Model\Magento\Config\Mutable $mutableConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
    ) {
        $this->mutableConfig = $mutableConfig;
        $this->globalDataHelper = $globalDataHelper;

        parent::__construct($helperFactory, $modelFactory);
    }

    /**
     * @return bool
     */
    protected function canExecute()
    {
        if (!$this->globalDataHelper->getValue('use_mutable_config')) {
            return false;
        }

        return parent::canExecute();
    }

    public function aroundGetValue($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getValue', $interceptor, $callback, $arguments);
    }

    protected function processGetValue($interceptor, \Closure $callback, array $arguments)
    {
        $path      = isset($arguments[0]) ? $arguments[0] : null;
        $scope     = isset($arguments[1]) ? $arguments[1] : null;
        $scopeCode = isset($arguments[2]) ? $arguments[2] : null;

        if (!is_string($path) || !is_string($scope)) {
            return $callback(...$arguments);
        }

        $overriddenValue = $this->mutableConfig->getValue($path, $scope, $scopeCode);
        if ($overriddenValue !== null) {
            return $overriddenValue;
        }

        return $callback(...$arguments);
    }
}
