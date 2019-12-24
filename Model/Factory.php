<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Model factory
 */
namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\Factory
 */
class Factory
{
    protected $helperFactory;
    protected $objectManager;

    //########################################

    /**
     * Construct
     *
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->helperFactory = $helperFactory;
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $modelName
     * @param array $arguments
     * @return \Ess\M2ePro\Model\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($modelName, array $arguments = [])
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $modelName = str_replace('_', '\\', $modelName);

        $model = $this->objectManager->create('\Ess\M2ePro\Model\\'.$modelName, $arguments);

        if (!$model instanceof \Ess\M2ePro\Model\AbstractModel) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\AbstractModel', $modelName)
            );
        }

        return $model;
    }

    //########################################
}
