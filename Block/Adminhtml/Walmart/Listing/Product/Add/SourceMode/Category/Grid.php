<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category;

use Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Product\Grid
{
    private $selectedIds = [];

    private $currentCategoryId = null;

    /** @var \Ess\M2ePro\Helper\Magento\Category */
    private $magentoCategoryHelper;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    public function __construct(
        ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation,
        \Ess\M2ePro\Helper\Magento\Category $magentoCategoryHelper,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Ess\M2ePro\Helper\Magento\Product $magentoProductHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->magentoCategoryHelper = $magentoCategoryHelper;
        $this->databaseHelper = $databaseHelper;
        parent::__construct(
            $excludeSimpleProductsInVariation,
            $magentoProductCollectionFactory,
            $type,
            $websiteFactory,
            $magentoProductHelper,
            $globalDataHelper,
            $context,
            $backendHelper,
            $dataHelper,
            $data
        );
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->removeColumn('visibility');
        $this->removeColumn('status');
        $this->removeColumn('websites');

        return $this;
    }

    private function getCollectionIds()
    {
        $ids = $this->getData('collection_ids');

        if ($ids !== null) {
            return $ids;
        }

        $ids = $this->magentoCategoryHelper->getProductsFromCategories(
            [$this->getCurrentCategoryId()],
            $this->_getStore()->getId()
        );

        $this->setData('collection_ids', $ids);

        return $ids;
    }

    protected function _prepareMassaction()
    {
        $this->getMassactionBlock()->setFormFieldName('ids');

        $ids = $this->getRequest()->getPost($this->getMassactionBlock()->getFormFieldNameInternal(), '');

        if ($this->getRequest()->isXmlHttpRequest() && !$this->getRequest()->getParam('category_change')) {
            return parent::_prepareMassaction();
        }

        $ids = array_filter(explode(',', $ids));
        $ids = array_merge($ids, $this->getSelectedIds());
        $ids = array_intersect($ids, $this->getCollectionIds());
        $ids = array_values(array_unique($ids));

        $this->getRequest()->setPostValue($this->getMassactionBlock()->getFormFieldNameInternal(), implode(',', $ids));

        $this->css->add(
            <<<CSS

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

    public function setSelectedIds(array $ids)
    {
        $this->selectedIds = $ids;

        return $this;
    }

    public function getSelectedIds()
    {
        return $this->selectedIds;
    }

    public function setCurrentCategoryId($currentCategoryId)
    {
        $this->currentCategoryId = $currentCategoryId;

        return $this;
    }

    public function getCurrentCategoryId()
    {
        return $this->currentCategoryId;
    }

    /**
     * @inheritdoc
     */
    public function setCollection($collection)
    {
        $from = $collection->getSelect()->getPart('from');
        if (empty($from['ccp'])) {
            $collection->joinTable(
                [
                    'ccp' => $this->databaseHelper
                        ->getTableNameWithPrefix('catalog_category_product'),
                ],
                'product_id=entity_id',
                ['category_id' => 'category_id']
            );

            $collection->addFieldToFilter('category_id', $this->currentCategoryId);
        }

        parent::setCollection($collection);
    }

    public function getSelectedProductsCallback()
    {
        return <<<JS
var add_category_products = function(callback) {

    saveSelectedProducts(function(transport) {

        new Ajax.Request('{$this->getUrl('*/*/getSessionProductsIds', ['_current' => true])}', {
            method: 'get',
            onSuccess: function(transport) {
                var massGridObj = {$this->getMassactionBlock()->getJsObjectName()};

                massGridObj.initialCheckedString = massGridObj.checkedString;

                var response = transport.responseText.evalJSON();
                var ids = response['ids'].join(',');

                if (ids == '') {
                    AddListingObj.alert('{$this->__('Please select the Products you want to perform the Action on.')}');
                    return false;
                }

                AddListingObj.add(ids, 'view', '');
            }
        });

    });
};
JS;
    }

    protected function _toHtml()
    {
        $html = parent::_toHtml();

        if ($this->getRequest()->getParam('category_change')) {
            $checkedString = implode(',', array_intersect($this->getCollectionIds(), $this->selectedIds));

            $this->js->add(
                <<<JS
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
<script type="text/javascript">
    {$this->getSelectedProductsCallback()}
</script>
HTML;
    }
}
