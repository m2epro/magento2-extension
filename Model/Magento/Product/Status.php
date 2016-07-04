<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Status extends \Ess\M2ePro\Model\AbstractModel
{
    protected $resourceModel;
    protected $productResource;

    protected $_productAttributes  = array();

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceModel,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceModel = $resourceModel;
        $this->productResource = $productResource;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function _getProductAttribute($attribute)
    {
        if (empty($this->_productAttributes[$attribute])) {
            $this->_productAttributes[$attribute] = $this->productResource->getAttribute($attribute);
        }
        return $this->_productAttributes[$attribute];
    }

    protected function _getReadAdapter()
    {
        return $this->resourceModel->getConnection();
    }

    //########################################

    public function getProductStatus($productIds, $storeId = null)
    {
        $statuses = array();

        $attribute      = $this->_getProductAttribute('status');
        $attributeTable = $attribute->getBackend()->getTable();
        $adapter        = $this->_getReadAdapter();

        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        if ($storeId === null || $storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $select = $adapter->select()
                ->from($attributeTable, array('entity_id', 'value'))
                ->where('entity_id IN (?)', $productIds)
                ->where('attribute_id = ?', $attribute->getAttributeId())
                ->where('store_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID);

            $rows = $adapter->fetchPairs($select);
        } else {
            $select = $adapter->select()
                ->from(
                    array('t1' => $attributeTable),
                    array('entity_id', 'IF(t2.value_id>0, t2.value, t1.value) as value'))
                ->joinLeft(
                    array('t2' => $attributeTable),
                    't1.entity_id = t2.entity_id AND t1.attribute_id = t2.attribute_id AND t2.store_id = '.
                        (int)$storeId,
                    array('t1.entity_id')
                )
                ->where('t1.store_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                ->where('t1.attribute_id = ?', $attribute->getAttributeId())
                ->where('t1.entity_id IN(?)', $productIds);
            $rows = $adapter->fetchPairs($select);
        }

        foreach ($productIds as $productId) {
            if (isset($rows[$productId])) {
                $statuses[$productId] = $rows[$productId];
            } else {
                $statuses[$productId] = -1;
            }
        }

        return $statuses;
    }

    //########################################
}