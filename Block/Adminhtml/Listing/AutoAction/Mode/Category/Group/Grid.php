<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Group;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $isGridPrepared = false;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeCategoryGroupGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareGrid()
    {
        if (!$this->isGridPrepared) {
            parent::_prepareGrid();
            $this->isGridPrepared = true;
        }
        return $this;
    }

    public function prepareGrid()
    {
        return $this->_prepareGrid();
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection logs
        // ---------------------------------------
        $categoriesCollection = $this->activeRecordFactory->getObject('Listing\Auto\Category')->getCollection();
        $categoriesCollection->getSelect()->reset(\Zend_Db_Select::FROM);
        $categoriesCollection->getSelect()->from(
            array('mlac' => $this->activeRecordFactory->getObject('Listing\Auto\Category')
                ->getResource()->getMainTable())
        );
        $categoriesCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $categoriesCollection->getSelect()->columns(new \Zend_Db_Expr('GROUP_CONCAT(`category_id`)'));
        $categoriesCollection->getSelect()->where('mlac.group_id = main_table.id');

        $collection = $this->activeRecordFactory->getObject('Listing\Auto\Category\Group')->getCollection();
        $collection->addFieldToFilter('main_table.listing_id', $this->getRequest()->getParam('id'));
        $collection->getSelect()->columns(
            array('categories' => new \Zend_Db_Expr('('.$categoriesCollection->getSelect().')'))
        );
        // ---------------------------------------

        // we need sort by id also, because create_date may be same for some adjustment entries
        // ---------------------------------------
        if ($this->getRequest()->getParam('sort', 'create_date') == 'create_date') {
            $collection->setOrder('id', $this->getRequest()->getParam('dir', 'DESC'));
        }
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('title', array(
            'header'    => $this->__('Group'),
            'align'     => 'left',
            'type'      => 'text',
            'escape'    => true,
            'index'     => 'title',
            'filter_index' => 'title'
        ));

        $this->addColumn('categories', array(
            'header'    => $this->__('Categories'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'frame_callback' => array($this, 'callbackColumnCategories')
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'actions'   => array(
                0 => array(
                    'label' => $this->__('Edit Group'),
                    'value' => 'categoryStepOne'
                ),
                1 => array(
                    'label' => $this->__('Delete Group'),
                    'value' => 'categoryDeleteGroup'
                )
            ),
            'frame_callback' => array($this, 'callbackColumnActions')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------
    }

    //########################################

    public function callbackColumnCategories($value, $row, $column, $isExport)
    {
        $groupId = (int)$row->getData('id');
        $categories = array_filter(explode(',', $row->getData('categories')));
        $count = count($categories);

        if ($count == 0 || $count > 3) {
            $total = $this->__('Total');
            $html = "<strong>{$total}:&nbsp;</strong>&nbsp;{$count}";

            if (count($categories) > 3) {
                $details = $this->__('details');
                $html .= <<<HTML
&nbsp;
[<a href="javascript: void(0);" onclick="ListingAutoActionObj.categoryStepOne({$groupId});">{$details}</a>]
HTML;
            }

            return $html;
        }

        $html = '';
        $magentoCategoryHelper = $this->getHelper('Magento\Category');

        foreach ($categories as $categoryId) {
            $path = $magentoCategoryHelper->getPath($categoryId);

            if (empty($path)) {
                continue;
            }

            if ($html != '') {
                $html .= '<br/>';
            }

            $path = implode(' > ', $path);
            $html .= '<span style="font-style: italic;">' . $this->getHelper('Data')->escapeHtml($path) . '</span>';
        }

        return $html;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $actions = $column->getActions();
        $id = (int)$row->getData('id');

        if (count($actions) == 1) {
            $action = reset($actions);
            $onclick = 'ListingAutoActionObj[\''.$action['value'].'\']('.$id.');';
            return '<a href="javascript: void(0);" onclick="' . $onclick . '">'.$action['label'].'</a>';
        }

        $optionsHtml = '<option></option>';

        foreach ($actions as $option) {
            $optionsHtml .= <<<HTML
            <option value="{$option['value']}">{$option['label']}</option>
HTML;
        }

        return <<<HTML
<div style="padding: 5px;">
    <select class="admin__control-select"
            style="margin: auto; display: block;"
            onchange="ListingAutoActionObj[this.value]({$id});">
        {$optionsHtml}
    </select>
</div>
HTML;
    }

    //########################################

    public function getRowUrl($item)
    {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/listing_autoAction/getCategoryGroupGrid', array('_current' => true));
    }

    //########################################
}