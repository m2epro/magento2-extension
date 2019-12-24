<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data;

/**
 * Class \Ess\M2ePro\Helper\Data\GlobalData
 */
class GlobalData extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $registryModel;

    //########################################

    public function __construct(
        \Magento\Framework\Registry $registryModel,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->registryModel = $registryModel;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getValue($key)
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        return $this->registryModel->registry($globalKey);
    }

    public function setValue($key, $value)
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $this->registryModel->register($globalKey, $value, !$this->getHelper('Module')->isDevelopmentEnvironment());
    }

    //########################################

    public function unsetValue($key)
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $this->registryModel->unregister($globalKey);
    }

    //########################################
}
