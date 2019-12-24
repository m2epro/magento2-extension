<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\Chooser;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\Chooser\Sku
 */
class Sku extends AbstractGrid
{
    protected $_eavAttSetCollection;
    protected $_catalogType;
    protected $_cpCollection;
    protected $_cpCollectionInstance;

    //########################################

    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $eavAttSetCollection,
        \Magento\Catalog\Model\Product\Type $catalogType,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $cpCollection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_eavAttSetCollection = $eavAttSetCollection;
        $this->_catalogType = $catalogType;
        $this->_cpCollection = $cpCollection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('skuChooserGrid_'.$this->getId());
        }

        $form = $this->getJsFormObject();
        $this->setRowClickCallback("$form.chooserGridRowClick.bind($form)");
        $this->setCheckboxCheckCallback("$form.chooserGridCheckboxCheck.bind($form)");
        $this->setRowInitCallback("$form.chooserGridRowInit.bind($form)");
        $this->setDefaultSort('sku');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    //########################################

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $selected = $this->_getSelectedProducts();
            if (empty($selected)) {
                $selected = '';
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('sku', ['in' => $selected]);
            } else {
                $this->getCollection()->addFieldToFilter('sku', ['nin' => $selected]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = $this->_getCpCollectionInstance()->setStoreId(
            0
        )->addAttributeToSelect(
            'name',
            'type_id',
            'attribute_set_id'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _getCpCollectionInstance()
    {
        if (!$this->_cpCollectionInstance) {
            $this->_cpCollectionInstance = $this->_cpCollection->create();
        }
        return $this->_cpCollectionInstance;
    }

    /**
     * Define Cooser Grid Columns and filters
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_products',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'sku',
                'use_index' => true
            ]
        );

        $this->addColumn(
            'entity_id',
            ['header' => $this->__('ID'), 'sortable' => true, 'width' => '60px', 'index' => 'entity_id']
        );

        $this->addColumn(
            'type',
            [
                'header' => $this->__('Type'),
                'width' => '60px',
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_catalogType->getOptionArray()
            ]
        );

        $sets = $this->_eavAttSetCollection->create()->setEntityTypeFilter(
            $this->_getCpCollectionInstance()->getEntity()->getTypeId()
        )->load()->toOptionHash();

        $this->addColumn(
            'set_name',
            [
                'header' => $this->__('Attribute Set Name'),
                'width' => '100px',
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets
            ]
        );

        $this->addColumn(
            'chooser_sku',
            ['header' => __('SKU'), 'name' => 'chooser_sku', 'width' => '80px', 'index' => 'sku']
        );
        $this->addColumn(
            'chooser_name',
            ['header' => $this->__('Product Name'), 'name' => 'chooser_name', 'index' => 'name']
        );

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/getRuleConditionChooserHtml', [
            '_current'          => true,
            'current_grid_id'   => $this->getId(),
            'collapse'          => null
        ]);
    }

    protected function _getSelectedProducts()
    {
        return $this->getRequest()->getPost('selected', []);
    }

    //########################################
}
