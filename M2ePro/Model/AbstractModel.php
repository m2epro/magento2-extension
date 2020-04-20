<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use Magento\Framework\DataObject;

/**
 * Class \Ess\M2ePro\Model\AbstractModel
 */
abstract class AbstractModel extends DataObject
{
    protected $helperFactory;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;

        parent::__construct($data);
    }

    //########################################

    public function getCacheLifetime()
    {
        return 60*60*24;
    }

    public function getCacheGroupTags()
    {
        $modelName = str_replace('Ess\M2ePro\Model\\', '', $this->getHelper('Client')->getClassName($this));

        $tags[] = $modelName;

        if (strpos($modelName, '\\') !== false) {
            $allComponents = $this->getHelper('Component')->getComponents();
            $modelNameComponent = substr($modelName, 0, strpos($modelName, '\\'));

            if (in_array(strtolower($modelNameComponent), array_map('strtolower', $allComponents))) {
                $modelNameOnlyModel = substr($modelName, strpos($modelName, '\\')+1);
                $tags[] = $modelNameComponent;
                $tags[] = $modelNameOnlyModel;
            }
        }

        $tags = array_unique($tags);
        $tags = array_map('strtolower', $tags);

        return $tags;
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
