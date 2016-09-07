<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $categoryFactory;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context, $backendHelper, $data);
    }
    
    //########################################

    public function getStoreId()
    {
        return !is_null($this->getData('store_id'))
            ? $this->getData('store_id') : \Magento\Store\Model\Store::DISTRO_STORE_ID;
    }

    public function setStoreId($storeId)
    {
        $this->setData('store_id',$storeId);
        return $this;
    }

    //########################################

    public function setCollection($collection)
    {
        $this->_prepareCache(clone $collection);
        parent::setCollection($collection);
    }
    
    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     */
    protected function _prepareCache($collection)
    {
        $stmt = $collection->getSelect()->query();

        $ids = array();
        foreach ($stmt as $item) {
            $ids = array_merge($ids,array_map('intval',explode('/',$item['path'])));
        }
        $ids = array_unique($ids);

        if (empty($ids)) {
            return;
        }

        /* @var $attribute \Magento\Eav\Model\Attribute */
        $attribute = $collection->getFirstItem()->getResource()->getAttribute('name');

        $resource = $collection->getResource();

        $tableName = \Magento\Catalog\Model\Category::ENTITY . '_entity_' . $attribute->getBackendType();

        $dbSelect1 = $resource->getConnection()
            ->select()
            ->from($resource->getTable($tableName), new \Zend_Db_Expr('MAX(`store_id`)'))
            ->where("`entity_id` = `ccev`.`entity_id`")
            ->where("`attribute_id` = `ccev`.`attribute_id`")
            ->where("`store_id` = 0 OR `store_id` = ?",$this->getStoreId());

        $dbSelect2 = $resource->getConnection()
            ->select()
            ->from(
                array('ccev' => $resource->getTable($tableName)),
                array('name' => 'value','category_id' => 'entity_id')
            )
            ->where('ccev.entity_id IN ('.implode(',',$ids).')')
            ->where('ccev.attribute_id = ?', $attribute->getAttributeId())
            ->where('ccev.store_id = ('.$dbSelect1->__toString().')');

        $cacheData = array();

        foreach ($resource->getConnection()->fetchAll($dbSelect2) as $row) {
            $cacheData[$row['category_id']] = $row['name'];
        }
        $this->setData('categories_cache', $cacheData);
    }

    //########################################

    public function callbackColumnMagentoCategory($value, $row, $column, $isExport)
    {
        $ids = explode('/',$row->getPath());

        $categoriesCache = $this->getData('categories_cache');
        $path = '';
        foreach ($ids as $id) {
            if (!isset($categoriesCache[$id])) {
                continue;
            }
            $path != '' && $path .= ' > ';
            $path .= $categoriesCache[$id];
        }

        return $this->getHelper('Data')->escapeHtml($path);
    }

    //########################################

    public function getMultipleRows($item)
    {
        return false;
    }

    //########################################
}