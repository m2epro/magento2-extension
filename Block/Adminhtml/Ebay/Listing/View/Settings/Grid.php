<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings;

use Ess\M2ePro\Model\Ebay\Template\Manager;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product as EbayProductResource;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $productFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Manager */
    protected $templateManager;

    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
    private $motorsAttribute = null;

    private $productsMotorsData = [];

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionDataHelper;

    private \Ess\M2ePro\Model\Ebay\Promotion\DashboardUrlGenerator $dashboardUrlGenerator;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        \Ess\M2ePro\Model\Ebay\Promotion\DashboardUrlGenerator $dashboardUrlGenerator,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productFactory = $productFactory;
        $this->templateManager = $templateManager;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->componentEbayMotors = $componentEbayMotors;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->sessionDataHelper = $sessionDataHelper;
        $this->dashboardUrlGenerator = $dashboardUrlGenerator;
        $this->campaignRepository = $campaignRepository;
        parent::__construct($context, $backendHelper, $dataHelper, $globalDataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingViewGrid' . $this->listing->getId());

        $this->css->addFile('ebay/template.css');
        $this->css->addFile('ebay/listing/grid.css');

        $this->showAdvancedFilterProductsOption = false;

        if ($this->isMotorsAvailable()) {
            $this->motorsAttribute = $this->productFactory->create()->getResource()->getAttribute(
                $this->componentEbayMotors->getAttribute($this->getMotorsType())
            );
        }
    }

    protected function _prepareCollection(): Grid
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setListing($this->listing);
        $collection->setStoreId($this->listing->getStoreId());

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
                'status' => 'status',
                'additional_data' => 'additional_data',
            ],
            '{{table}}.listing_id=' . (int)$this->listing->getId()
        );

        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',

                'end_date' => 'end_date',
                'start_date' => 'start_date',
                'online_title' => 'online_title',
                'online_sku' => 'online_sku',
                'available_qty' => new \Zend_Db_Expr('(CAST(online_qty AS SIGNED) - CAST(online_qty_sold AS SIGNED))'),
                'ebay_item_id' => 'ebay_item_id',
                'online_main_category' => 'online_main_category',
                'online_qty_sold' => 'online_qty_sold',
                'online_start_price' => 'online_start_price',
                'online_current_price' => 'online_current_price',
                'online_reserve_price' => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',

                'template_category_id' => 'template_category_id',
                'template_category_secondary_id' => 'template_category_secondary_id',
                'template_store_category_id' => 'template_store_category_id',
                'template_store_category_secondary_id' => 'template_store_category_secondary_id',

                'template_return_policy_mode' => 'template_return_policy_mode',
                'template_shipping_mode' => 'template_shipping_mode',
                'template_description_mode' => 'template_description_mode',
                'template_selling_format_mode' => 'template_selling_format_mode',
                'template_synchronization_mode' => 'template_synchronization_mode',

                'template_return_policy_id' => 'template_return_policy_id',
                'template_shipping_id' => 'template_shipping_id',
                'template_description_id' => 'template_description_id',
                'template_selling_format_id' => 'template_selling_format_id',
                'template_synchronization_id' => 'template_synchronization_id',

                EbayProductResource::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID =>
                    EbayProductResource::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
            ]
        );
        $eiTable = $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable();
        $collection->joinTable(
            ['ei' => $eiTable],
            'id=ebay_item_id',
            [
                'item_id' => 'item_id',
            ],
            null,
            'left'
        );

        $etcTable = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()->getMainTable();
        $collection->joinTable(
            ['etc1' => $etcTable],
            'id=template_category_id',
            [
                'category_main_mode' => 'category_mode',
                'category_main_id' => 'category_id',
                'category_main_path' => 'category_path',
                'category_main_attribute' => 'category_attribute',
                'category_main_is_custom_template' => 'is_custom_template',
            ],
            null,
            'left'
        );
        $collection->joinTable(
            ['etc2' => $etcTable],
            'id=template_category_secondary_id',
            [
                'category_secondary_mode' => 'category_mode',
                'category_secondary_id' => 'category_id',
                'category_secondary_path' => 'category_path',
                'category_secondary_attribute' => 'category_attribute',
            ],
            null,
            'left'
        );
        $etocTable = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory')
                                               ->getResource()->getMainTable();
        $collection->joinTable(
            ['etsc1' => $etocTable],
            'id=template_store_category_id',
            [
                'store_category_main_mode' => 'category_mode',
                'store_category_main_id' => 'category_id',
                'store_category_main_path' => 'category_path',
                'store_category_main_attribute' => 'category_attribute',
            ],
            null,
            'left'
        );
        $collection->joinTable(
            ['etsc2' => $etocTable],
            'id=template_store_category_secondary_id',
            [
                'store_category_secondary_mode' => 'category_mode',
                'store_category_secondary_id' => 'category_id',
                'store_category_secondary_path' => 'category_path',
                'store_category_secondary_attribute' => 'category_attribute',
            ],
            null,
            'left'
        );
        if ($this->motorsAttribute) {
            $collection->joinAttribute(
                $this->motorsAttribute->getAttributeCode(),
                $this->motorsAttribute,
                'entity_id',
                null,
                'left',
                $this->getStoreId()
            );

            $collection->joinTable(
                [
                    'eea' => $this->databaseHelper
                        ->getTableNameWithPrefix('eav_entity_attribute'),
                ],
                'attribute_set_id=attribute_set_id',
                [
                    'is_motors_attribute_in_product_attribute_set' => 'entity_attribute_id',
                ],
                '{{table}}.attribute_id = ' . $this->motorsAttribute->getAttributeId(),
                'left'
            );
        }

        $tdTable = $this->activeRecordFactory->getObject('Template_Description')->getResource()->getMainTable();
        $etrpTable = $this->activeRecordFactory->getObject('Ebay_Template_ReturnPolicy')->getResource()->getMainTable();
        $tsfTable = $this->activeRecordFactory->getObject('Template_SellingFormat')->getResource()->getMainTable();
        $etsTable = $this->activeRecordFactory->getObject('Ebay_Template_Shipping')->getResource()->getMainTable();
        $tsTable = $this->activeRecordFactory->getObject('Template_Synchronization')->getResource()->getMainTable();
        $collection
            ->joinTable(
                ['td' => $tdTable],
                'id=template_description_id',
                ['description_policy_title' => 'title'],
                null,
                'left'
            )
            ->joinTable(
                ['etrp' => $etrpTable],
                'id=template_return_policy_id',
                ['return_policy_title' => 'title'],
                null,
                'left'
            )
            ->joinTable(
                ['tsf' => $tsfTable],
                'id=template_selling_format_id',
                ['selling_policy_title' => 'title'],
                null,
                'left'
            )
            ->joinTable(
                ['ets' => $etsTable],
                'id=template_shipping_id',
                ['shipping_policy_title' => 'title'],
                null,
                'left'
            )
            ->joinTable(
                ['ts' => $tsTable],
                'id=template_synchronization_id',
                ['synchronization_policy_title' => 'title'],
                null,
                'left'
            );

        if ($this->isFilterOrSortByPriceIsUsed(null, 'ebay_online_current_price')) {
            $collection->joinIndexerParent();
        }

        $this->setCollection($collection);
        $result = parent::_prepareCollection();

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->prepareExistingMotorsData();
        }

        return $result;
    }

    protected function _prepareColumns(): Grid
    {
        $this->addColumn('product_id', [
            'header' => $this->__('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => $this->__('Product Title / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('online_sku', [
            'header' => __('Channel SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'online_sku',
            'escape' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\OnlineSku::class,
        ]);

        if ($this->isMarketplaceSupportPromotedListingsCampaign()) {
            $this->addColumn('ebay_promotedListing_campaign', [
                'header' => $this->__('Campaign'),
                'align' => 'left',
                'type' => 'options',
                'index' => EbayProductResource::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
                'sortable' => false,
                'options' => $this->getCampaignOptions(),
            ]);
        }

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->addColumn('parts_motors_attribute_value', [
                'header' => $this->__('Compatibility'),
                'align' => 'left',
                'width' => '100px',
                'type' => 'options',
                'index' => $this->motorsAttribute->getAttributeCode(),
                'sortable' => false,
                'options' => [
                    1 => $this->__('Filled'),
                    0 => $this->__('Empty'),
                ],
                'frame_callback' => [$this, 'callbackColumnMotorsAttribute'],
                'filter_condition_callback' => [$this, 'callbackFilterMotorsAttribute'],
            ]);
        }

        $this->addColumn('category', [
            'header' => $this->__('eBay Categories'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter\Category::class,
            'frame_callback' => [$this, 'callbackColumnCategory'],
            'filter_condition_callback' => [$this, 'callbackFilterCategory'],
        ]);

        $this->addColumn('setting', [
            'index' => 'name',
            'header' => $this->__('Listing Policies Overrides'),
            'align' => 'left',
            'type' => 'text',
            'sortable' => false,
            'filter' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter\PolicySettings::class,
            'frame_callback' => [$this, 'callbackColumnSetting'],
            'filter_condition_callback' => [$this, 'callbackFilterSetting'],
            'column_css_class' => 'ebay-listing-grid-column-setting',
        ]);

        $this->addColumn('actions', [
            'header' => $this->__('Actions'),
            'align' => 'left',
            'type' => 'action',
            'index' => 'actions',
            'filter' => false,
            'sortable' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'field' => 'id',
            'group_order' => $this->getGroupOrder(),
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(): \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        // ---------------------------------------

        // Set mass-action
        // ---------------------------------------
        $this->_prepareMassactionGroup()
             ->_prepareMassactionItems();

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _prepareMassactionGroup(): Grid
    {
        $this->getMassactionBlock()->setGroups([
            'edit_settings' => $this->__('Edit Listing Policies Overrides'),
            'edit_categories_settings' => $this->__('Edit Category Settings'),
            'other' => $this->__('Other'),
        ]);

        return $this;
    }

    protected function _prepareMassactionItems(): Grid
    {
        // ---------------------------------------

        $this->getMassactionBlock()->addItem('editAllSettings', [
            'label' => $this->__('All Settings'),
            'url' => '',
        ], 'edit_settings');

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('editCategorySettings', [
            'label' => $this->__('Categories & Specifics'),
            'url' => '',
        ], 'edit_categories_settings');

        // ---------------------------------------

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->getMassactionBlock()->addItem('editMotors', [
                'label' => $this->__('Add Compatible Vehicles'),
                'url' => '',
            ], 'other');
        }

        $this->getMassactionBlock()->addItem('managePromotion', [
            'label' => __('Manage Discounts'),
            'url' => '',
        ], 'other');

        if ($this->isMarketplaceSupportPromotedListingsCampaign()) {
            $this->getMassactionBlock()->addItem('managePromotedListings', [
                'label' => __('Manage Promoted Listings'),
                'url' => '',
            ], 'other');
        }

        $this->getMassactionBlock()->addItem('moving', [
            'label' => $this->__('Move Item(s) to Another Listing'),
            'url' => '',
        ], 'other');

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('transferring', [
            'label' => $this->__('Sell on Another Marketplace'),
            'url' => '',
        ], 'other');

        // ---------------------------------------

        return $this;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport): string
    {
        $value = '<span>' . $this->dataHelper->escapeHtml($value) . '</span>';

        $sku = $row->getData('sku');
        if ($sku === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                                      ->setProductId($row->getData('entity_id'))
                                      ->getSku();
        }

        $value .= '<br/><strong>' . $this->__('SKU') . ':</strong>&nbsp;';
        $value .= '<span class="white-space-pre-wrap">' . $this->dataHelper->escapeHtml($sku) . '</span>';

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $row->getData('listing_product_id'));

        if ($listingProduct->getChildObject()->isVariationsReady()) {
            $additionalData = (array)json_decode($row->getData('additional_data'), true);
            $productAttributes = isset($additionalData['variations_sets'])
                ? array_keys($additionalData['variations_sets']) : [];

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">';
            $value .= implode(', ', $productAttributes);
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnCategory($value, $row, $column, $isExport): string
    {
        $html = '';

        $categories = $this->componentEbayCategory->getCategoryTitles();

        if ($row->getData('category_main_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            $html .= $this->getCategoryInfoHtml(
                $this->componentEbayCategory->getCategoryTitle(
                    \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN
                ),
                '<span style="color: red">' . $this->__('Not Set') . '</span>'
            );
        } else {
            $html .= $this->getEbayCategoryInfoHtml(
                $row,
                'category_main',
                $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN]
            );
        }

        $html .= $this->getEbayCategoryInfoHtml(
            $row,
            'category_secondary',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY]
        );

        $html .= $this->getStoreCategoryInfoHtml(
            $row,
            'category_main',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN]
        );

        $html .= $this->getStoreCategoryInfoHtml(
            $row,
            'category_secondary',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY]
        );

        return $html;
    }

    public function callbackColumnSetting($value, $row, $column, $isExport): string
    {
        $templatesNames = [
            Manager::TEMPLATE_SHIPPING => $this->__('Shipping'),
            Manager::TEMPLATE_RETURN_POLICY => $this->__('Return'),
            Manager::TEMPLATE_SELLING_FORMAT => $this->__('Selling'),
            Manager::TEMPLATE_DESCRIPTION => $this->__('Description'),
            Manager::TEMPLATE_SYNCHRONIZATION => $this->__('Synchronization'),
        ];

        // ---------------------------------------

        $modes = array_keys($templatesNames);
        $listingSettings = array_filter($modes, function ($templateNick) use ($row) {
            $templateMode = $row->getData('template_' . $templateNick . '_mode');

            return $templateMode == Manager::MODE_PARENT;
        });

        if (count($listingSettings) === count($templatesNames)) {
            return $this->__('Use from Listing Settings');
        }

        // ---------------------------------------

        $html = '';
        foreach ($templatesNames as $templateNick => $templateTitle) {
            $templateMode = $row->getData('template_' . $templateNick . '_mode');

            if ($templateMode == Manager::MODE_PARENT) {
                continue;
            }

            $templateLink = '';
            if ($templateMode == Manager::MODE_CUSTOM) {
                $templateLink = '<span>' . $this->__('Custom Settings') . '</span>';
            } elseif ($templateMode == Manager::MODE_TEMPLATE) {
                $id = (int)$row->getData('template_' . $templateNick . '_id');

                $url = $this->getUrl('m2epro/ebay_template/edit', [
                    'id' => $id,
                    'nick' => $templateNick,
                ]);

                $objTitle = $this->templateManager->setTemplate($templateNick)
                                                  ->getTemplateModel()
                                                  ->load($id)
                                                  ->getTitle();

                $templateLink = '<a href="' . $url . '" target="_blank">' . $objTitle . '</a>';
            }

            $html .= "<div style='padding: 2px 0 0 0px'>
                                    <strong>{$templateTitle}:</strong>
                                    <span style='padding: 0 0px 0 5px'>{$templateLink}</span>
                               </div>";
        }

        return $html;
    }

    public function callbackColumnMotorsAttribute($value, $row, $column, $isExport)
    {
        if (!$this->motorsAttribute) {
            return $this->__('N/A');
        }

        if (!$row->getData('is_motors_attribute_in_product_attribute_set')) {
            return $this->__('N/A');
        }

        $attributeCode = $this->motorsAttribute->getAttributeCode();
        $attributeValue = $row->getData($attributeCode);

        if (empty($attributeValue)) {
            return $this->__('N/A');
        }

        $motorsData = $this->productsMotorsData[$row->getData('listing_product_id')];

        $countOfItems = count($motorsData['items']);
        $countOfFilters = count($motorsData['filters']);
        $countOfGroups = count($motorsData['groups']);

        if ($countOfItems + $countOfFilters + $countOfGroups === 0) {
            return $this->__('N/A');
        }

        if ($this->componentEbayMotors->isTypeBasedOnEpids($this->getMotorsType())) {
            $motorsTypeTitle = 'ePIDs';
        } else {
            $motorsTypeTitle = 'kTypes';
        }

        $html = '<div style="padding: 4px; color: #666666">';
        $labelFilters = $this->__('Filters');
        $labelGroups = $this->__('Groups');

        if ($countOfItems > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$motorsTypeTitle}</span>:&nbsp;
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewItemPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfItems}</a><br/>
HTML;
        }

        if ($countOfFilters > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$labelFilters}</span>:&nbsp;
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewFilterPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfFilters}</a><br/>
HTML;
        }

        if ($countOfGroups > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$labelGroups}</span>:&nbsp;
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewGroupPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfGroups}</a>
HTML;
        }

        $html .= '</div>';

        return $html;
    }

    public function callbackFilterTitle($collection, $column)
    {
        $inputValue = $column->getFilter()->getValue();

        if ($inputValue !== null) {
            $fieldsToFilter = [
                ['attribute' => 'sku', 'like' => '%' . $inputValue . '%'],
                ['attribute' => 'name', 'like' => '%' . $inputValue . '%'],
            ];

            $collection->addFieldToFilter($fieldsToFilter);
        }
    }

    public function callbackFilterCategory($collection, $column)
    {
        $inputValue = $column->getFilter()->getValue('input');

        if ($inputValue !== null) {
            $fieldsToFilter = [
                ['attribute' => 'category_main_path', 'like' => '%' . $inputValue . '%'],
                ['attribute' => 'category_secondary_path', 'like' => '%' . $inputValue . '%'],
                ['attribute' => 'store_category_main_path', 'like' => '%' . $inputValue . '%'],
                ['attribute' => 'store_category_secondary_path', 'like' => '%' . $inputValue . '%'],
            ];

            if (is_numeric($inputValue)) {
                $fieldsToFilter[] = ['attribute' => 'category_main_id', 'eq' => $inputValue];
                $fieldsToFilter[] = ['attribute' => 'category_secondary_id', 'eq' => $inputValue];
                $fieldsToFilter[] = ['attribute' => 'store_category_main_id', 'eq' => $inputValue];
                $fieldsToFilter[] = ['attribute' => 'store_category_secondary_id', 'eq' => $inputValue];
            }

            $collection->addFieldToFilter($fieldsToFilter);
        }

        $selectValue = $column->getFilter()->getValue('select');
        if ($selectValue !== null) {
            $collection->addFieldToFilter('template_category_id', [($selectValue ? 'notnull' : 'null') => true]);
        }
    }

    public function callbackFilterSetting($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $inputValue = null;

        if (is_array($value) && isset($value['input'])) {
            $inputValue = $value['input'];
        } elseif (is_string($value)) {
            $inputValue = $value;
        }

        if ($inputValue !== null) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'description_policy_title', 'like' => '%' . $inputValue . '%'],
                    ['attribute' => 'return_policy_title', 'like' => '%' . $inputValue . '%'],
                    ['attribute' => 'selling_policy_title', 'like' => '%' . $inputValue . '%'],
                    ['attribute' => 'shipping_policy_title', 'like' => '%' . $inputValue . '%'],
                    ['attribute' => 'synchronization_policy_title', 'like' => '%' . $inputValue . '%'],
                ]
            );
        }

        if (isset($value['select'])) {
            switch ($value['select']) {
                case Manager::MODE_PARENT:
                    // no policy overrides
                    $collection->addAttributeToFilter(
                        'template_shipping_mode',
                        ['eq' => Manager::MODE_PARENT]
                    );
                    $collection->addAttributeToFilter(
                        'template_return_policy_mode',
                        ['eq' => Manager::MODE_PARENT]
                    );
                    $collection->addAttributeToFilter(
                        'template_description_mode',
                        ['eq' => Manager::MODE_PARENT]
                    );
                    $collection->addAttributeToFilter(
                        'template_selling_format_mode',
                        ['eq' => Manager::MODE_PARENT]
                    );
                    $collection->addAttributeToFilter(
                        'template_synchronization_mode',
                        ['eq' => Manager::MODE_PARENT]
                    );
                    break;
                case Manager::MODE_TEMPLATE:
                case Manager::MODE_CUSTOM:
                    // policy templates and custom settings
                    $collection->addAttributeToFilter(
                        [
                            [
                                'attribute' => 'template_shipping_mode',
                                'eq' => (int)$value['select'],
                            ],
                            [
                                'attribute' => 'template_return_policy_mode',
                                'eq' => (int)$value['select'],
                            ],
                            [
                                'attribute' => 'template_description_mode',
                                'eq' => (int)$value['select'],
                            ],
                            [
                                'attribute' => 'template_selling_format_mode',
                                'eq' => (int)$value['select'],
                            ],
                            [
                                'attribute' => 'template_synchronization_mode',
                                'eq' => (int)$value['select'],
                            ],
                        ]
                    );
                    break;
            }
        }
    }

    public function callbackFilterMotorsAttribute($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        if (!$this->motorsAttribute) {
            return;
        }

        $attributeCode = $this->motorsAttribute->getAttributeCode();

        if ($value == 1) {
            $collection->addFieldToFilter($attributeCode, ['notnull' => true]);
            $collection->addFieldToFilter($attributeCode, ['neq' => '']);
            $collection->addFieldToFilter(
                'is_motors_attribute_in_product_attribute_set',
                ['notnull' => true]
            );
        } else {
            $collection->addFieldToFilter(
                [
                    ['attribute' => $attributeCode, 'null' => true],
                    ['attribute' => $attributeCode, 'eq' => ''],
                    ['attribute' => 'is_motors_attribute_in_product_attribute_set', 'null' => true],
                ]
            );
        }
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/ebay_listing/view', ['_current' => true]);
    }

    public function getRowUrl($item): bool
    {
        return false;
    }

    private function getEbayCategoryInfoHtml($row, $modeNick, $modeTitle): string
    {
        $helper = $this->dataHelper;
        $mode = $row->getData($modeNick . '_mode');

        if ($mode === null || $mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') . ' > ';
            $category .= $helper->escapeHtml(
                $this->magentoAttributeHelper->getAttributeLabel(
                    $row->getData($modeNick . '_attribute'),
                    $this->listing->getStoreId()
                )
            );
        } else {
            $category = $helper->escapeHtml($row->getData($modeNick . '_path')) . ' (' . $row->getData(
                $modeNick . '_id'
            ) . ')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getStoreCategoryInfoHtml($row, $modeNick, $modeTitle)
    {
        $helper = $this->dataHelper;
        $mode = $row->getData('store_' . $modeNick . '_mode');

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') . ' > ';
            $category .= $helper->escapeHtml(
                $this->magentoAttributeHelper->getAttributeLabel(
                    $row->getData('store_' . $modeNick . '_attribute'),
                    $this->listing->getStoreId()
                )
            );
        } else {
            $category = $helper->escapeHtml($row->getData('store_' . $modeNick . '_path')) .
                ' (' . $row->getData('store_' . $modeNick . '_id') . ')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getCategoryInfoHtml($modeTitle, $category): string
    {
        return <<<HTML
    <div>
        <span style="text-decoration: underline">{$modeTitle}</span>
        <p style="padding: 2px 0 0 10px">{$category}</p>
    </div>
HTML;
    }

    private function isMotorsAvailable(): bool
    {
        return $this->isMotorEpidsAvailable() || $this->isMotorKtypesAvailable();
    }

    private function isMotorEpidsAvailable()
    {
        return $this->listing->getChildObject()->isPartsCompatibilityModeEpids();
    }

    private function isMotorKtypesAvailable()
    {
        return $this->listing->getChildObject()->isPartsCompatibilityModeKtypes();
    }

    private function getMotorsType()
    {
        if (!$this->isMotorsAvailable()) {
            return null;
        }

        if ($this->isMotorEpidsAvailable()) {
            return $this->componentEbayMotors->getEpidsTypeByMarketplace(
                $this->listing->getMarketplaceId()
            );
        }

        return \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
    }

    protected function getGroupOrder(): array
    {
        return [
            'edit_general_settings' => $this->__('Edit Listing Policies Overrides'),
            'edit_categories_settings' => $this->__('Edit Category Settings'),
            'other' => $this->__('Other'),
        ];
    }

    protected function getColumnActionsItems(): array
    {
        $actions = [
            'editCategories' => [
                'caption' => $this->__('Categories & Specifics'),
                'group' => 'edit_categories_settings',
                'field' => 'id',
                'onclick_action' => "EbayListingViewSettingsGridObj.actions['editCategorySettingsAction']",
            ],
        ];

        // ---------------------------------------

        $actions['editAll'] = [
            'caption' => $this->__('All Settings'),
            'group' => 'edit_general_settings',
            'field' => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editAllSettingsAction\']',
        ];

        // ---------------------------------------

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $actions['editMotors'] = [
                'caption' => $this->__('Add Compatible Vehicles'),
                'group' => 'other',
                'field' => 'id',
                'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editMotorsAction\']',
            ];
        }

        $actions['remapProduct'] = [
            'caption' => $this->__('Link to another Magento Product'),
            'group' => 'other',
            'field' => 'id',
            'only_remap_product' => true,
            'style' => 'width: 215px',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'remapProductAction\']',
        ];

        // ---------------------------------------

        return $actions;
    }

    protected function _toHtml(): string
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
            EbayListingViewSettingsGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        /** @var \Ess\M2ePro\Helper\Data $helper */
        $helper = $this->dataHelper;

        // ---------------------------------------
        $this->jsPhp->addConstants($helper->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay\Category::class));
        $this->jsPhp->addConstants($helper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Manager::class));
        // ---------------------------------------

        // ---------------------------------------
        $this->jsUrl->addUrls($helper->getControllerActions('Ebay\Listing', ['_current' => true]));

        $this->jsUrl->add(
            $this->getUrl('*/ebay_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId(),
            ]),
            'ebay_log_listing_product/index'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId(),
                'back' => $helper->makeBackUrlParam(
                    '*/ebay_listing/view',
                    ['id' => $this->listing->getId()]
                ),
            ]),
            'logViewUrl'
        );

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListingGrid'), 'moveToListingGridHtml');
        $this->jsUrl->add($this->getUrl('*/listing_moving/prepareMoveToListing'), 'prepareData');
        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListing'), 'moveToListing');

        $this->jsUrl->add(
            $this->getUrl('*/ebay_template/editListingProductsPolicy'),
            'ebay_template/editListingProductsPolicy'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_template/saveListingProductsPolicy'),
            'ebay_template/saveListingProductsPolicy'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotion/openGridPromotion'),
            'ebay_promotion/openGridPromotion'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotion/openGridDiscount'),
            'ebay_promotion/openGridDiscount'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotion/synchronizePromotions'),
            'ebay_promotion/synchronizePromotions'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotion/updateItemPromotion'),
            'ebay_promotion/updateItemPromotion'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/getCampaignGrid'),
            'promotedListing/getCampaignGrid'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/getCreateCampaignForm'),
            'promotedListing/getCreateCampaignForm'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/getUpdateCampaignForm'),
            'promotedListing/getUpdateCampaignForm'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/refreshCampaigns'),
            'promotedListing/refreshCampaigns'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/createCampaign'),
            'promotedListing/createCampaign'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/updateCampaign'),
            'promotedListing/updateCampaign'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/deleteCampaign'),
            'promotedListing/deleteCampaign'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/addItemsToCampaign'),
            'promotedListing/addItemsToCampaign'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_promotedListing/deleteItemsFromCampaign'),
            'promotedListing/deleteItemsFromCampaign'
        );

        $this->jsUrl->addUrls($helper->getControllerActions('Ebay_Listing_Settings_Motors'));
        // ---------------------------------------

        $taskCompletedWarningMessage = '"%task_title%" Task has completed with warnings.'
            . ' <a target="_blank" href="%url%">View Log</a> for details.';

        $taskCompletedErrorMessage = '"%task_title%" Task has completed with errors. '
            . ' <a target="_blank" href="%url%">View Log</a> for details.';

        if ($this->componentEbayMotors->isTypeBasedOnEpids($this->getMotorsType())) {
            $motorsTypeTitle = 'ePID';
        } else {
            $motorsTypeTitle = 'kType';
        }

        //------------------------------
        $this->jsTranslator->addTranslations([
            'Edit Return Policy Setting' => $this->__('Edit Return Policy Setting'),
            'Edit Shipping Policy Setting' => $this->__('Edit Shipping Policy Setting'),
            'Edit Description Policy Setting' => $this->__('Edit Description Policy Setting'),
            'Edit Selling Policy Setting' => $this->__('Edit Selling Policy Setting'),
            'Edit Synchronization Policy Setting' => $this->__('Edit Synchronization Policy Setting'),
            'Edit Settings' => $this->__('Edit Settings'),
            'For' => $this->__('For'),
            'Category Settings' => $this->__('Category Settings'),
            'Specifics' => $this->__('Specifics'),
            'Compatibility Attribute ePIDs' => $this->__('Compatibility Attribute ePIDs'),
            'Add Compatible Vehicles' => $this->__('Add Compatible Vehicles'),
            'Save Filter' => $this->__('Save Filter'),
            'Save as Group' => $this->__('Save as Group'),
            'Set Note' => $this->__('Set Note'),
            'View Items' => $this->__('Selected %items_title%s', $motorsTypeTitle),
            'Selected Items' => $this->__('Selected %items_title%s', $motorsTypeTitle),
            'Remove' => $this->__('Remove'),
            'Motor Item' => $motorsTypeTitle,
            'View Groups' => $this->__('Selected Groups'),
            'View Filters' => $this->__('Selected Filters'),
            'Selected Filters' => $this->__('Selected Filters'),
            'Selected Groups' => $this->__('Selected Groups'),
            'Note' => $this->__('Note'),
            'Filter' => $this->__('Filter'),
            'Group' => $this->__('Group'),
            'kType' => $this->__('kType'),
            'ePID' => $this->__('ePID'),
            'Type' => $this->__('Type'),
            'Year From' => $this->__('Year From'),
            'Year To' => $this->__('Year To'),
            'Body Style' => $this->__('Body Style'),
            'task_completed_message' => $this->__('Task completed. Please wait ...'),
            'task_completed_success_message' => $this->__('"%task_title%" Task has completed.'),
            'sending_data_message' => $this->__('Sending %product_title% Product(s) data on eBay.'),
            'View Full Product Log.' => $this->__('View Full Product Log.'),
            'The Listing was locked by another process. Please try again later.' =>
                $this->__('The Listing was locked by another process. Please try again later.'),
            'Listing is empty.' => $this->__('Listing is empty.'),
            'Please select Items.' => $this->__('Please select Items.'),
            'Please select Action.' => $this->__('Please select Action.'),
            'popup_title' => $this->__('Moving eBay Items'),
            'task_completed_warning_message' => $this->__($taskCompletedWarningMessage),
            'task_completed_error_message' => $this->__($taskCompletedErrorMessage),
            'Add New Listing' => $this->__('Add New Listing'),
            'Manage Discounts' => __('Manage Discounts'),
            'Assign' => __('Assign'),
            'Refresh Discounts' => __('Refresh Discounts'),
            'Select Discount' => __('Select Discount'),
            'Manage Discount' => __('Manage Discount'),
            'Create New Discount' => __('Create New Discount'),
        ]);

        $temp = $this->sessionDataHelper->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;
        $ignoreListings = \Ess\M2ePro\Helper\Json::encode([$this->listing->getId()]);

        $motorsType = '';
        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $motorsType = $this->getMotorsType();
        }

        $this->js->add(
            <<<JS
    M2ePro.productsIdsForList = '{$productsIdsForList}';

    M2ePro.customData.componentMode = '{$component}';
    M2ePro.customData.gridId = '{$this->getId()}';
    M2ePro.customData.ignoreListings = '{$ignoreListings}';
JS
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'EbayListingAutoActionInstantiation',
        'M2ePro/Ebay/Listing/View/Settings/Grid',
        'M2ePro/Ebay/Listing/View/Settings/Motors',
        'M2ePro/Ebay/Listing/Category',
        'M2ePro/Ebay/Listing/Transferring',
        'M2ePro/Ebay/Promotion'
    ], function(){

        window.EbayListingViewSettingsGridObj = new EbayListingViewSettingsGrid(
            '{$this->getId()}',
            '{$this->listing->getId()}',
            '{$this->listing->getMarketplaceId()}',
            '{$this->listing->getAccountId()}'
        );
        EbayListingViewSettingsGridObj.afterInitPage();
        EbayListingViewSettingsGridObj.movingHandler.setProgressBar('listing_view_progress_bar');
        EbayListingViewSettingsGridObj.movingHandler.setGridWrapper('listing_view_content_container');

        EbayListingViewSettingsMotorsObj = new EbayListingViewSettingsMotors({$this->listing->getId()},'{$motorsType}');
        window.EbayListingCategoryObj = new EbayListingCategory(EbayListingViewSettingsGridObj);
        window.EbayListingTransferringObj = new EbayListingTransferring(
            {$this->listing->getId()}
        );

        window.PromotionObj = new Promotion(
            '{$this->listing->getAccountId()}',
            '{$this->listing->getMarketplaceId()}',
            '{$this->dashboardUrlGenerator->generate($this->listing->getMarketplaceId())}'
        );

        window.CampaignObj = new Campaign(
            {$this->listing->getAccountId()},
            {$this->listing->getMarketplaceId()},
            '{$this->getId()}',
            '{$this->listing->getMarketplace()->getTitle()}'
        )
    });
JS
        );

        // ---------------------------------------
        if ($this->getRequest()->getParam('auto_actions')) {
            $this->js->add(
                <<<JS
require([
    'EbayListingAutoActionInstantiation'
], function() {
    ListingAutoActionObj.loadAutoActionHtml();
});
JS
            );
        }

        // ---------------------------------------

        return parent::_toHtml();
    }

    private function prepareExistingMotorsData()
    {
        $motorsHelper = $this->componentEbayMotors;

        $products = $this->getCollection()->getItems();

        $productsMotorsData = [];

        $items = [];
        $filters = [];
        $groups = [];

        foreach ($products as $product) {
            if (!$product->getData('is_motors_attribute_in_product_attribute_set')) {
                continue;
            }

            $productId = $product->getData('listing_product_id');

            $attributeCode = $this->motorsAttribute->getAttributeCode();
            $attributeValue = $product->getData($attributeCode);

            $productsMotorsData[$productId] = $motorsHelper->parseAttributeValue($attributeValue);

            $items = array_merge($items, array_keys($productsMotorsData[$productId]['items']));
            $filters = array_merge($filters, $productsMotorsData[$productId]['filters']);
            $groups = array_merge($groups, $productsMotorsData[$productId]['groups']);
        }

        //-------------------------------
        $typeIdentifier = $motorsHelper->getIdentifierKey($this->getMotorsType());

        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from(
                                               $motorsHelper->getDictionaryTable($this->getMotorsType()),
                                               [$typeIdentifier]
                                           )
                                           ->where('`' . $typeIdentifier . '` IN (?)', $items);

        if ($motorsHelper->isTypeBasedOnEpids($this->getMotorsType())) {
            $select->where('scope = ?', $motorsHelper->getEpidsScopeByType($this->getMotorsType()));
        }

        $existedItems = $select->query()->fetchAll(\PDO::FETCH_COLUMN);
        //-------------------------------

        //-------------------------------
        $filtersTable = $this->databaseHelper
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter');
        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from(
                                               $filtersTable,
                                               ['id']
                                           )
                                           ->where('`id` IN (?)', $filters);

        $existedFilters = $select->query()->fetchAll(\PDO::FETCH_COLUMN);
        //-------------------------------

        //-------------------------------
        $groupsTable = $this->databaseHelper->getTableNameWithPrefix('m2epro_ebay_motor_group');
        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from(
                                               $groupsTable,
                                               ['id']
                                           )
                                           ->where('`id` IN (?)', $groups);

        $existedGroups = $select->query()->fetchAll(\PDO::FETCH_COLUMN);
        //-------------------------------

        foreach ($productsMotorsData as $productId => $productMotorsData) {
            foreach ($productMotorsData['items'] as $item => $itemData) {
                if (!in_array($item, $existedItems)) {
                    unset($productsMotorsData[$productId]['items'][$item]);
                }
            }

            foreach ($productMotorsData['filters'] as $key => $filterId) {
                if (!in_array($filterId, $existedFilters)) {
                    unset($productsMotorsData[$productId]['filters'][$key]);
                }
            }

            foreach ($productMotorsData['groups'] as $key => $groupId) {
                if (!in_array($groupId, $existedGroups)) {
                    unset($productsMotorsData[$productId]['groups'][$key]);
                }
            }
        }

        $this->productsMotorsData = $productsMotorsData;

        return $this;
    }

    private function isMarketplaceSupportPromotedListingsCampaign(): bool
    {
        return in_array(
            $this->listing->getMarketplaceId(),
            \Ess\M2ePro\Helper\Component\Ebay::PROMOTED_LISTINGS_MARKETPLACE
        );
    }

    private function getCampaignOptions(): array
    {
        $campaigns = $this->campaignRepository
            ->getAllByAccountIdAndMarketplaceId($this->listing->getAccountId(), $this->listing->getMarketplaceId());

        $campaignNames = [];
        foreach ($campaigns as $campaign) {
            $campaignNames[$campaign->getId()] = $campaign->getName();
        }

        return $campaignNames;
    }
}
