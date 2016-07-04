<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

abstract class AbstractHelper extends \Ess\M2ePro\Helper\AbstractHelper
{
    const RETURN_TYPE_IDS = 1;
    const RETURN_TYPE_ARRAYS = 2;
    const RETURN_TYPE_OBJECTS = 3;

    private $objectManager;

    //########################################

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->objectManager = $objectManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    protected function _getIdsFromInput($input, $idKey = 'id')
    {
        if (!is_array($input) || empty($input)) {
            return array();
        }

        $ids = array();
        foreach ($input as $entity) {
            if (is_numeric($entity)) {
                $ids[] = (int)$entity;
            } elseif (is_array($entity)) {
                $ids[] = (int)$entity[$idKey];
            } elseif (is_object($entity)) {
                $ids[] = (int)$entity->getId();
            }
        }

        return $ids;
    }

    protected function _getIdFromInput($input)
    {
        if (!is_numeric($input) && !is_object($input)) {
            return false;
        }

        if (is_object($input)) {
            return (int)$input->getId();
        }

        return (int)$input;
    }

    // ---------------------------------------

    protected function _convertCollectionToReturnType($collection, $returnType)
    {
        switch ($returnType) {
            case self::RETURN_TYPE_IDS:
                return $collection->getAllIds();

            case self::RETURN_TYPE_OBJECTS:
                return $collection->getItems();

            case self::RETURN_TYPE_ARRAYS:
            default:
                $entities = $collection->toArray();
                return $entities['items'];
        }
    }

    protected function _convertFetchNumArrayToReturnType(array $fetchArray, $returnType, $modelName)
    {
        if (empty($fetchArray)) {
            return array();
        }

        $result = array();
        foreach ($fetchArray as $fetchItem) {
            $item = array_shift($fetchItem);

            if ($returnType == self::RETURN_TYPE_IDS) {
                $result[] = $item;
                continue;
            }

            $model = $this->objectManager->create($modelName)->load($item);
            if ($returnType == self::RETURN_TYPE_OBJECTS) {
                $result[] = $model;
            } elseif ($returnType == self::RETURN_TYPE_ARRAYS) {
                $result[] = $model->toArray();
            }
        }

        return $result;
    }

    //########################################
}