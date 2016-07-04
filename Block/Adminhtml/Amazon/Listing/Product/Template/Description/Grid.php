<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Description;

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

    protected $customCollectionFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->customCollectionFactory = $customCollectionFactory;
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
        $this->setFilterVisibility(false);
        $this->setDefaultSort('id');
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
            \Ess\M2ePro\Helper\View\Amazon::NICK, 'Template\Description'
        )->getCollection();

        $descriptionCollection->addFieldToFilter('marketplace_id', $this->getMarketplaceId());

        $preparedCollection = $this->customCollectionFactory->create();
        $preparedCollection->setConnection($this->resourceConnection->getConnection());

        $data = $descriptionCollection->getData();
        $preparedData = [];
        foreach ($data as $item) {
            if (!$this->getCheckNewAsinAccepted()) {
                $item['description_template_action_status'] = self::ACTION_STATUS_READY_TO_BE_ASSIGNED;
                $preparedData[] = $item;
                continue;
            }

            if (!$item['is_new_asin_accepted']) {
                $item['description_template_action_status'] = self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED;
                $preparedData[] = $item;
                continue;
            }

            $variationProductsIds = $this->getVariationsProductsIds();

            if (!empty($variationProductsIds)) {
                $detailsModel = $this->modelFactory->getObject('Amazon\Marketplace\Details');
                $detailsModel->setMarketplaceId($this->getMarketplaceId());
                $themes = $detailsModel->getVariationThemes($item['product_data_nick']);

                if (empty($themes)) {
                    $item['description_template_action_status'] = self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED;
                    $preparedData[] = $item;
                    continue;
                }
            }

            $item['description_template_action_status'] = self::ACTION_STATUS_READY_TO_BE_ASSIGNED;
            $preparedData[] = $item;
            continue;
        }

        if (!empty($preparedData)) {
            usort($preparedData, function($a, $b)
            {
                return $a["description_template_action_status"] < $b["description_template_action_status"];
            });

            foreach ($preparedData as $item) {
                $preparedCollection->addItem(new \Magento\Framework\DataObject($item));
            }
        }

        $preparedCollection->setCustomSize(count($preparedData));
        $this->setCollection($preparedCollection);

        parent::_prepareCollection();

        $preparedCollection->setCustomIsLoaded(true);

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', array(
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $this->addColumn('status', array(
            'header'       => $this->__('Status/Reason'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '140px',
            'index'        => 'title',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        $this->addColumn('action', array(
            'header'       => $this->__('Action'),
            'align'        => 'left',
            'type'         => 'number',
            'width'        => '55px',
            'index'        => 'id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnAction')
        ));
    }

    protected function _prepareLayout()
    {
        $this->setChild('refresh_button',
            $this->createBlock('Magento\Button')
                ->setData(array(
                    'id' => 'description_template_refresh_btn',
                    'label'     => $this->__('Refresh'),
                    'class'     => 'action primary',
                    'onclick'   => $this->getJsObjectName().'.reload()'
                ))
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
        $templateDescriptionEditUrl = $this->getUrl('*/amazon_template_description/edit', array(
            'id' => $row->getData('id')
        ));

        $title = $this->getHelper('Data')->escapeHtml($row->getData('title'));

        $categoryWord = $this->__('Category');
        $categoryPath = !empty($row['category_path']) ? "{$row['category_path']} ({$row['browsenode_id']})"
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
        switch($row->getData('description_template_action_status')) {
            case self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED:
                return '<span style="color: #808080;">' .
                    $this->__('New ASIN/ISBN feature is disabled') . '</span>';
                break;
            case self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED:
                return '<span style="color: #808080;">' .
                    $this->__(
                        'Selected Category doesn\'t support Variational Products'
                    ) . '</span>';
                break;
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

        switch($row->getData('description_template_action_status')) {
            case self::ACTION_STATUS_NEW_ASIN_NOT_ACCEPTED:
                return '<span style="color: #808080;">' . $assignText . '</span>';
                break;
            case self::ACTION_STATUS_VARIATIONS_NOT_SUPPORTED:
                return '<span style="color: #808080;">' . $assignText . '</span>';
                break;
        }

        return '<a href="javascript:void(0);"'
            . 'onclick="' . $this->getMapToTemplateJsFn() . '(this, '
            . $value . $mapToAsin .');">'.$assignText.'</a>';
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewTemplateDescriptionUrl(), 'newTemplateDescriptionUrl');

        $this->js->add(
<<<JS
    $$('#amazonTemplateDescriptionGrid div.grid th').each(function(el) {
        el.style.padding = '5px 5px';
    });

    $$('#amazonTemplateDescriptionGrid div.grid td').each(function(el) {
        el.style.padding = '5px 5px';
    });
JS
);

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewTemplateDescriptionsGrid', array(
            '_current' => true,
            '_query' => array(
                'check_is_new_asin_accepted' => $this->getCheckNewAsinAccepted()
            ),
            'products_ids' => implode(',', $this->getProductsIds()),
        ));
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
                \Ess\M2ePro\Helper\View\Amazon::NICK, 'Listing\Product', $productsIds[0]
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
    onclick="ListingGridHandlerObj.templateDescriptionHandler.createTemplateDescriptionInNewTab(
        '{$templateDescriptionEditUrl}');">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateDescriptionUrl()
    {
        return $this->getUrl('*/amazon_template_description/new', array(
            'is_new_asin_accepted'  => $this->getCheckNewAsinAccepted(),
            'marketplace_id'        => $this->getMarketplaceId(),
            'wizard' => $this->getHelper('Module\Wizard')
                ->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK),
            'close_on_save' => 1
        ));
    }

    // ---------------------------------------

    protected function getParentListingProduct()
    {
        $productsIds = $this->getProductsIds();
        if (count($productsIds) == 1 && empty($this->listingProduct)) {
            $listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\View\Amazon::NICK, 'Listing\Product', $productsIds[0]
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
        if (is_null($this->variationProductsIds)) {
            $this->variationProductsIds = array();

            /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
            $collection = $this->parentFactory->getObject(
                \Ess\M2ePro\Helper\View\Amazon::NICK, 'Listing\Product'
            )->getCollection();
            $collection->addFieldToFilter('additional_data', array('notnull' => true));
            $collection->addFieldToFilter('id', array('in' => $this->getProductsIds()));
            $collection->addFieldToFilter('is_variation_parent', 1);

            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns(
                array(
                    'main_table.id'
                )
            );

            $this->variationProductsIds = $collection->getData();
        }

        return $this->variationProductsIds;
    }

    //########################################
}