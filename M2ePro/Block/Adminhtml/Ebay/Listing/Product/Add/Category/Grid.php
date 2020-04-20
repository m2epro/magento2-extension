<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Grid
{
    private $selectedIds = [];

    private $currentCategoryId = null;

    //########################################

    private function getCollectionIds()
    {
        $ids = $this->getData('collection_ids');

        if ($ids !== null) {
            return $ids;
        }

        $ids = $this->getHelper('Magento\Category')->getProductsFromCategories(
            [$this->getCurrentCategoryId()],
            $this->_getStore()->getId()
        );

        $this->setData('collection_ids', $ids);
        return $ids;
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->getMassactionBlock()->setFormFieldName('ids');

        $ids = $this->getRequest()->getPost($this->getMassactionBlock()->getFormFieldNameInternal());

        if ($this->getRequest()->isXmlHttpRequest() && !$this->getRequest()->getParam('category_change')) {
            return parent::_prepareMassaction();
        }

        $ids = array_filter(explode(',', $ids));
        $ids = array_merge($ids, $this->getSelectedIds());
        $ids = array_intersect($ids, $this->getCollectionIds());
        $ids = array_values(array_unique($ids));

        $this->getRequest()->setPostValue($this->getMassactionBlock()->getFormFieldNameInternal(), implode(',', $ids));

        $this->css->add(<<<CSS

            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:first-child {
                width: 100% !important;
                margin-top: 1.1em;
            }
            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:last-child {
                width: 100% !important;
            }

            #{$this->getId()} > .admin__data-grid-header >
            .admin__data-grid-header-row:last-child .admin__control-support-text {
                margin-left: 0;
            }

            #{$this->getId()} > .admin__data-grid-header >
            .admin__data-grid-header-row:last-child .mass-select-wrap {
                margin-left: -1.3em !important;
            }
CSS
        );

        return parent::_prepareMassaction();
    }

    //########################################

    public function setSelectedIds(array $ids)
    {
        $this->selectedIds = $ids;
        return $this;
    }

    public function getSelectedIds()
    {
        return $this->selectedIds;
    }

    // ---------------------------------------

    public function setCurrentCategoryId($currentCategoryId)
    {
        $this->currentCategoryId = $currentCategoryId;
        return $this;
    }

    public function getCurrentCategoryId()
    {
        return $this->currentCategoryId;
    }

    //########################################

    /**
     * @inheritdoc
     */
    public function setCollection($collection)
    {
        $collection->joinTable(
            [
                'ccp' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_category_product')
            ],
            'product_id=entity_id',
            ['category_id' => 'category_id']
        );

        $collection->addFieldToFilter('category_id', $this->currentCategoryId);

        parent::setCollection($collection);
    }

    //########################################

    protected function getSelectedProductsCallback()
    {
        return <<<JS
(function() {
    return function(callback) {

        saveSelectedProducts(function(transport) {

            new Ajax.Request('{$this->getUrl('*/*/getSessionProductsIds', array('_current' => true))}', {
                method: 'get',
                onSuccess: function(transport) {
                    var massGridObj = {$this->getMassactionBlock()->getJsObjectName()};

                    massGridObj.initialCheckedString = massGridObj.checkedString;

                    var response = transport.responseText.evalJSON();
                    var ids = response['ids'].join(',');

                    callback(ids);
                }
            });

        });
    }
})()
JS;
    }

    //########################################

    protected function _toHtml()
    {
        $html = parent::_toHtml();

        if ($this->getRequest()->getParam('category_change')) {
            $checkedString = implode(',', array_intersect($this->getCollectionIds(), $this->selectedIds));

            $this->js->add(<<<JS
    {$this->getMassactionBlock()->getJsObjectName()}.checkedString = '{$checkedString}';
    {$this->getMassactionBlock()->getJsObjectName()}.initCheckboxes();
    {$this->getMassactionBlock()->getJsObjectName()}.checkCheckboxes();
    {$this->getMassactionBlock()->getJsObjectName()}.updateCount();

    {$this->getMassactionBlock()->getJsObjectName()}.initialCheckedString =
        {$this->getMassactionBlock()->getJsObjectName()}.checkedString;
JS
            );
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            return $html;
        }

        return <<<HTML

<div class="page-layout-admin-2columns-left" style="margin-top: 20px;">
    <div class="page-columns">
        <div class="main-col">
            {$html}
        </div>
        <div class="side-col">
            {$this->getTreeBlock()->toHtml()}
        </div>
    </div>
</div>
HTML;
    }

    //########################################
}
