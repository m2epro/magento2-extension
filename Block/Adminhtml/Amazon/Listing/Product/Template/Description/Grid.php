<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Description;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Description\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    const ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED = 1;
    const ACTION_STATUS_VARIATIONS_NOT_SUPPORTED = 2;
    const ACTION_STATUS_READY_TO_BE_ASSIGNED = 3;

    protected $attributesSetsIds;
    protected $marketplaceId;
    protected $listingProduct;
    protected $variationProductsIds;

    protected $checkNewAsinAccepted = false;
    protected $productsIds;
    protected $mapToTemplateJsFn = 'ListingGridHandlerObj.templateDescriptionHandler.mapToTemplateDescription';
    protected $createNewTemplateJsFn =
        'ListingGridHandlerObj.templateDescriptionHandler.createTemplateDescriptionInNewTab';

    protected $resourceConnection;

    protected $cacheData = [];

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
     * @param boolean $checkNewAsinAccepted
     */
    public function setCheckNewAsinAccepted($checkNewAsinAccepted)
    {
        $this->checkNewAsinAccepted = $checkNewAsinAccepted;
    }

    /**
     * @return boolean
     */
    public function getCheckNewAsinAccepted()
    {
        return (bool) $this->checkNewAsinAccepted;
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

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        $this->setId('amazonTemplateDescriptionGrid');

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

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Collection $descriptionCollection */
        $descriptionCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\View\Amazon::NICK,
            'Template\Description'
        )->getCollection();

        $descriptionCollection->addFieldToFilter('marketplace_id', $this->getMarketplaceId());

        $this->setCollection($descriptionCollection);
        $this->prepareCacheData();

        return parent::_prepareCollection();
    }

    // ---------------------------------------

    private function prepareCacheData()
    {
        $this->cacheData = [];
        $tempCollection = clone $this->getCollection();

        foreach ($tempCollection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Amazon\Template\Description $item */

            if (!$this->getCheckNewAsinAccepted()) {
                $this->cacheData[$item->getId()] = self::ACTION_STATUS_READY_TO_BE_ASSIGNED;
                continue;
            }

            if (!$item->getChildObject()->getData('is_new_asin_accepted')) {
                $this->cacheData[$item->getId()] = self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED;
                continue;
            }

            $variationProductsIds = $this->getVariationsProductsIds();
            if (!empty($variationProductsIds)) {
                $detailsModel = $this->modelFactory->getObject('Amazon_Marketplace_Details');
                $detailsModel->setMarketplaceId($this->getMarketplaceId());

                $themes = $detailsModel->getVariationThemes($item->getChildObject()->getData('product_data_nick'));
                if (empty($themes)) {
                    $this->cacheData[$item->getId()] = self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED;
                    continue;
                }
            }

            $this->cacheData[$item->getId()] = self::ACTION_STATUS_READY_TO_BE_ASSIGNED;
            continue;
        }
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
            'escape'       => false,
            'sortable'     => true,
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('status', [
            'header'       => $this->__('Status/Reason'),
            'align'        => 'left',
            'type'         => 'options',
            'options'      => [
                self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED => $this->__(
                    'New ASIN/ISBN feature is disabled'
                ),
                self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED => $this->__(
                    'Selected Category doesn\'t support Variational Products'
                ),
                self::ACTION_STATUS_READY_TO_BE_ASSIGNED => $this->__(
                    'Ready to be assigned'
                ),
            ],
            'width'        => '140px',
            'index'        => 'description_template_action_status',
            'filter_index' => 'description_template_action_status',
            'sortable'     => false,
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
            'frame_callback' => [$this, 'callbackColumnStatus']
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
                    'id' => 'description_template_refresh_btn',
                    'label'     => $this->__('Refresh'),
                    'class'     => 'action primary',
                    'onclick'   => $this->getJsObjectName().'.reload()'
                ])
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Collection $collection */

        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'title LIKE ? OR category_path LIKE ? OR browsenode_id LIKE ?',
            '%'.$value.'%'
        );
    }

    protected function callbackFilterStatus($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Collection $collection */

        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Amazon\Template\Description $item */

            if ($this->cacheData[$item->getId()] != $value) {
                $collection->removeItemByKey($item->getId());
            }
        }
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
        $templateDescriptionEditUrl = $this->getUrl('*/amazon_template_description/edit', [
            'id' => $row->getData('id'),
            'wizard' => $this->getHelper('Module\Wizard')->isActive(
                \Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK
            ),
            'close_on_save' => true
        ]);

        $title = $this->getHelper('Data')->escapeHtml($row->getData('title'));

        $categoryWord = $this->__('Category');
        $categoryPath = $row->getChildObject()->getData('category_path');
        $browseNode   = $row->getChildObject()->getData('browsenode_id');

        $categoryPath = !empty($categoryPath) ? "{$categoryPath} ({$browseNode})"
                                              : $this->__('N/A');

        return <<<HTML
<a target="_blank" href="{$templateDescriptionEditUrl}">{$title}</a>
<div>
    <span style="font-weight: bold">{$categoryWord}</span>: <span style="color: #505050">{$categoryPath}</span><br/>
</div>
HTML;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $status = $this->cacheData[$row->getId()];

        switch ($status) {
            case self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED:
                return '<span style="color: #808080;">' .
                    $this->__('New ASIN/ISBN feature is disabled') . '</span>';

            case self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED:
                return '<span style="color: #808080;">' .
                    $this->__(
                        'Selected Category doesn\'t support Variational Products'
                    ) . '</span>';
        }

        return '<span style="color: green;">' . $this->__('Ready to be assigned') . '</span>';
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');
        $mapToAsin = '';

        if ($this->getCheckNewAsinAccepted()) {
            $mapToAsin = ',1';
        }

        switch ($this->cacheData[$row->getId()]) {
            case self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED:
                return '<span style="color: #808080;">' . $assignText . '</span>';
            case self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED:
                return '<span style="color: #808080;">' . $assignText . '</span>';
        }

        return '<a href="javascript:void(0);"'
            . 'onclick="' . $this->getMapToTemplateJsFn() . '(this, '
            . $value . $mapToAsin .');">'.$assignText.'</a>';
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewTemplateDescriptionUrl(), 'newTemplateDescriptionUrl');

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'check_is_new_asin_accepted' => $this->getCheckNewAsinAccepted(),
                'map_to_template_js_fn' => $this->getMapToTemplateJsFn(),
                'create_new_template_js_fn' => $this->getCreateNewTemplateJsFn()
            ],
            'products_ids' => implode(',', $this->getProductsIds()),
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
                \Ess\M2ePro\Helper\View\Amazon::NICK,
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
        $templateDescriptionEditUrl = $this->getNewTemplateDescriptionUrl();

        $messageTxt = $this->__('Description Policies are not found for current Marketplace.');
        $linkTitle = $this->__('Create New Description Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    id="template_description_addNew_link"
    onclick="{$this->getCreateNewTemplateJsFn()}('{$templateDescriptionEditUrl}');">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateDescriptionUrl()
    {
        return $this->getUrl('*/amazon_template_description/new', [
            'is_new_asin_accepted'  => $this->getCheckNewAsinAccepted(),
            'marketplace_id'        => $this->getMarketplaceId(),
            'wizard' => $this->getHelper('Module\Wizard')
                ->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK),
            'close_on_save' => 1
        ]);
    }

    // ---------------------------------------

    protected function getParentListingProduct()
    {
        $productsIds = $this->getProductsIds();
        if (count($productsIds) == 1 && empty($this->listingProduct)) {
            $listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\View\Amazon::NICK,
                'Listing\Product',
                $productsIds[0]
            );
            if ($listingProduct->getChildObject()->getVariationManager()->isVariationParent()) {
                $this->listingProduct = $listingProduct;
            }
        }
        return $this->listingProduct;
    }

    // ---------------------------------------

    protected function getVariationsProductsIds()
    {
        if ($this->variationProductsIds === null) {
            $this->variationProductsIds = [];

            /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
            $collection = $this->parentFactory->getObject(
                \Ess\M2ePro\Helper\View\Amazon::NICK,
                'Listing\Product'
            )->getCollection();
            $collection->addFieldToFilter('additional_data', ['notnull' => true]);
            $collection->addFieldToFilter('id', ['in' => $this->getProductsIds()]);
            $collection->addFieldToFilter('is_variation_parent', 1);

            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns(
                [
                    'main_table.id'
                ]
            );

            $this->variationProductsIds = $collection->getData();
        }

        return $this->variationProductsIds;
    }

    //########################################
}
