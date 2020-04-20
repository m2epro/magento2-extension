<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Category;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Category\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $productsIds = [];
    protected $magentoCategoryIds = [];
    protected $marketplaceId;

    protected $mapToTemplateJsFn = 'ListingGridHandlerObj.templateCategoryHandler.mapToTemplateCategory';
    protected $createNewTemplateJsFn = 'ListingGridHandlerObj.templateCategoryHandler.createTemplateCategoryInNewTab';

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    /**
     * @return string
     */
    public function getMapToTemplateJsFn()
    {
        return $this->mapToTemplateJsFn;
    }

    /**
     * @param string $mapToTemplateLink
     */
    public function setMapToTemplateJsFn($mapToTemplateLink)
    {
        $this->mapToTemplateJsFn = $mapToTemplateLink;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getCreateNewTemplateJsFn()
    {
        return $this->createNewTemplateJsFn;
    }

    /**
     * @param string $createNewTemplateJsFn
     */
    public function setCreateNewTemplateJsFn($createNewTemplateJsFn)
    {
        $this->createNewTemplateJsFn = $createNewTemplateJsFn;
    }

    // ---------------------------------------

    /**
     * @param mixed $productsIds
     */
    public function setProductsIds($productsIds)
    {
        $this->productsIds = $productsIds;
    }

    /**
     * @return mixed
     */
    public function getProductsIds()
    {
        return $this->productsIds;
    }

    // ---------------------------------------

    public function setMagentoCategoryIds($magentoCategoryIds)
    {
        $this->magentoCategoryIds = $magentoCategoryIds;
    }

    public function getMagentoCategoryIds()
    {
        return $this->magentoCategoryIds;
    }

    // ---------------------------------------

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartTemplateCategoryGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(true);
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    // ---------------------------------------

    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Collection $categoryCollection */
        $categoryCollection = $this->activeRecordFactory->getObject('Walmart_Template_Category')->getCollection();
        $categoryCollection->addFieldToFilter('marketplace_id', $this->getMarketplaceId());

        $this->setCollection($categoryCollection);

        return parent::_prepareCollection();
    }

    // ---------------------------------------

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter_index' => 'title',
            'sortable'     => true,
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('action', [
            'header'       => $this->__('Action'),
            'align'        => 'left',
            'type'         => 'number',
            'width'        => '55px',
            'index'        => 'id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => [$this, 'callbackColumnAction']
        ]);
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'refresh_button',
            $this->createBlock('Magento\Button')
                ->setData([
                    'id' => 'category_template_refresh_btn',
                    'label'     => $this->__('Refresh'),
                    'class'     => 'action primary',
                    'onclick'   => $this->getJsObjectName().'.reload()'
                ])
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    //########################################

    public function getMainButtonsHtml()
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $templateCategoryEditUrl = $this->getUrl('*/walmart_template_category/edit', [
            'id' => $row->getData('id'),
            'wizard' => $this->getHelper('Module\Wizard')->isActive(
                \Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK
            ),
            'close_on_save' => true
        ]);

        $title = $this->getHelper('Data')->escapeHtml($row->getData('title'));

        $categoryWord = $this->__('Category');
        $categoryPath = !empty($row['category_path']) ? "{$row['category_path']} ({$row['browsenode_id']})"
                                                      : $this->__('N/A');

        return <<<HTML
<a target="_blank" href="{$templateCategoryEditUrl}">{$title}</a>
<div>
    <span style="font-weight: bold">{$categoryWord}</span>: <span style="color: #505050">{$categoryPath}</span><br/>
</div>
HTML;
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');

        return <<<HTML
<a href="javascript:void(0);" onclick="{$this->getMapToTemplateJsFn()}(this, {$value});">{$assignText}</a>
HTML;
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Collection $collection */

        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'title LIKE ? OR category_path LIKE ? OR browsenode_id LIKE ?',
            '%'.$value.'%'
        );
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewTemplateCategoryUrl(), 'newTemplateCategoryUrl');

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'map_to_template_js_fn' => $this->getMapToTemplateJsFn(),
                'create_new_template_js_fn' => $this->getCreateNewTemplateJsFn()
            ],
            'products_ids' => implode(',', $this->getProductsIds()),
            'magento_categories_ids' => implode(',', $this->getMagentoCategoryIds()),
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function getMarketplaceId()
    {
        if (empty($this->marketplaceId)) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $productsIds = $this->getProductsIds();
            $listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\View\Walmart::NICK,
                'Listing\Product',
                $productsIds[0]
            );
            $this->marketplaceId = $listingProduct->getListing()->getMarketplaceId();
        }

        return $this->marketplaceId;
    }

    // ---------------------------------------

    protected function setNoTemplatesText()
    {
        $templateCategoryEditUrl = $this->getNewTemplateCategoryUrl();

        $messageTxt = $this->__('Category Policies are not found for current Marketplace.');
        $linkTitle = $this->__('Create New Category Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    id="template_category_addNew_link"
    onclick="{$this->getCreateNewTemplateJsFn()}('{$templateCategoryEditUrl}');">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateCategoryUrl()
    {
        return $this->getUrl('*/walmart_template_category/new', [
            'marketplace_id'        => $this->getMarketplaceId(),
            'close_on_save' => 1
        ]);
    }

    //########################################
}
