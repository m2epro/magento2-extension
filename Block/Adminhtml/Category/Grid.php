<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $categoryCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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

        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->joinAttribute(
            'name', 'catalog_category/name', 'entity_id', NULL, 'inner', $this->getStoreId()
        );
        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => $ids]
        ]);

        $cacheData = array();
        foreach ($collection->getItems() as $item) {
            /** @var \Magento\Catalog\Model\Category $item */
            $cacheData[$item->getData('entity_id')] = $item->getData('name');
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