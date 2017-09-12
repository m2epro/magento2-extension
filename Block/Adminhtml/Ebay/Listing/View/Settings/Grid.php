<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings;

use Ess\M2ePro\Model\Ebay\Template\Manager;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
    private $motorsAttribute = NULL;

    private $productsMotorsData = array();

    protected $resourceConnection;
    protected $productFactory;
    protected $templateManager;
    protected $magentoProductCollectionFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->productFactory = $productFactory;
        $this->templateManager = $templateManager;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->ebayFactory = $ebayFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewSettingsGrid'.$this->listing->getId());
        // ---------------------------------------

        $this->css->addFile('ebay/template.css');

        $this->showAdvancedFilterProductsOption = false;

        if ($this->isMotorsAvailable()) {

            $attributeCode = $this->getHelper('Component\Ebay\Motors')->getAttribute($this->getMotorsType());
            $this->motorsAttribute = $this->productFactory->create()->getResource()->getAttribute($attributeCode);
        }
    }

    //########################################

    protected function _prepareCollection()
    {
        // ---------------------------------------
        // Get collection
        // ---------------------------------------
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setListing($this->listing);

        if ($this->isFilterOrSortByPriceIsUsed(null, 'ebay_online_current_price')) {
            $collection->setIsNeedToUseIndexerParent(true);
        }

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
                'ebay_status' => 'status',
                'additional_data' => 'additional_data'
            ],
            '{{table}}.listing_id='.(int)$this->listing->getId()
        );

        $elpTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',

                'template_category_id'  => 'template_category_id',
                'template_other_category_id'  => 'template_other_category_id',

                'template_payment_mode'  => 'template_payment_mode',
                'template_payment_id'  => 'template_payment_id',
                'template_payment_custom_id'  => 'template_payment_custom_id',

                'template_shipping_mode' => 'template_shipping_mode',
                'template_shipping_id' => 'template_shipping_id',
                'template_shipping_custom_id' => 'template_shipping_custom_id',

                'template_return_policy_mode' => 'template_return_policy_mode',
                'template_return_policy_id' => 'template_return_policy_id',
                'template_return_policy_custom_id' => 'template_return_policy_custom_id',

                'template_description_mode' => 'template_description_mode',
                'template_description_id' => 'template_description_id',
                'template_description_custom_id' => 'template_description_custom_id',

                'template_selling_format_mode'  => 'template_selling_format_mode',
                'template_selling_format_id'  => 'template_selling_format_id',
                'template_selling_format_custom_id'  => 'template_selling_format_custom_id',

                'template_synchronization_mode' => 'template_synchronization_mode',
                'template_synchronization_id' => 'template_synchronization_id',
                'template_synchronization_custom_id' => 'template_synchronization_custom_id',

                'end_date'              => 'end_date',
                'start_date'            => 'start_date',
                'online_title'          => 'online_title',
                'online_sku'            => 'online_sku',
                'available_qty'         => new \Zend_Db_Expr('(online_qty - online_qty_sold)'),
                'ebay_item_id'          => 'ebay_item_id',
                'online_category'       => 'online_category',
                'online_qty_sold'       => 'online_qty_sold',
                'online_start_price'    => 'online_start_price',
                'online_current_price'  => 'online_current_price',
                'online_reserve_price'  => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',
            ]
        );
        $eiTable = $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable();
        $collection->joinTable(
            ['ei' => $eiTable],
            'id=ebay_item_id',
            [
                'item_id' => 'item_id',
            ],
            NULL,
            'left'
        );

        $etcTable = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getResource()->getMainTable();
        $collection->joinTable(
            ['etc' => $etcTable],
            'id=template_category_id',
            [
                'category_main_mode'      => 'category_main_mode',
                'category_main_id'        => 'category_main_id',
                'category_main_path'      => 'category_main_path',
                'category_main_attribute' => 'category_main_attribute',
            ],
            NULL,
            'left'
        );
        $etocTable = $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')
            ->getResource()->getMainTable();
        $collection->joinTable(
            ['etoc' => $etocTable],
            'id=template_other_category_id',
            [
                'category_secondary_mode'      => 'category_secondary_mode',
                'category_secondary_id'        => 'category_secondary_id',
                'category_secondary_path'      => 'category_secondary_path',
                'category_secondary_attribute' => 'category_secondary_attribute',

                'store_category_main_mode'      => 'store_category_main_mode',
                'store_category_main_id'        => 'store_category_main_id',
                'store_category_main_path'      => 'store_category_main_path',
                'store_category_main_attribute' => 'store_category_main_attribute',

                'store_category_secondary_mode'      => 'store_category_secondary_mode',
                'store_category_secondary_id'        => 'store_category_secondary_id',
                'store_category_secondary_path'      => 'store_category_secondary_path',
                'store_category_secondary_attribute' => 'store_category_secondary_attribute',
            ],
            NULL,
            'left'
        );

        if ($this->motorsAttribute) {
            $collection->joinAttribute(
                $this->motorsAttribute->getAttributeCode(),
                $this->motorsAttribute, 'entity_id', NULL, 'left', $this->getStoreId()
            );

            $collection->joinTable(
                array('eea' => $this->resourceConnection->getTableName('eav_entity_attribute')),
                'attribute_set_id=attribute_set_id',
                array(
                    'is_motors_attribute_in_product_attribute_set' => 'entity_attribute_id',
                ),
                '{{table}}.attribute_id = ' . $this->motorsAttribute->getAttributeId(),
                'left'
            );
        }

        // ---------------------------------------

        // Set collection to grid
        if ($collection->isNeedUseIndexerParent()) {
            $collection->joinIndexerParent();
        }

        $this->setCollection($collection);
        $result = parent::_prepareCollection();

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->prepareExistingMotorsData();
        }

        return $result;
    }

    protected function _prepareColumns()
    {

        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->addColumn('parts_motors_attribute_value', [
                'header'    => $this->__('Compatibility'),
                'align'     => 'left',
                'width'     => '100px',
                'type'      => 'options',
                'index'     => $this->motorsAttribute->getAttributeCode(),
                'sortable'  => false,
                'options'   => array(
                    1 => $this->__('Filled'),
                    0 => $this->__('Empty')
                ),
                'frame_callback' => array($this, 'callbackColumnMotorsAttribute'),
                'filter_condition_callback' => array($this, 'callbackFilterMotorsAttribute'),
            ]);
        }

        $title = $this->__('eBay Categories');
        $isExistsListingSettingsOverwrites = $this->isExistsListingSettingsOverwrites();
        if ($isExistsListingSettingsOverwrites) {
            $title = $this->__('eBay Categories / Listing Policies Overrides');
        }
        $this->addColumn('category', [
            'header'    => $title,
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'is_exists_listing_settings_overwrites' => $isExistsListingSettingsOverwrites,
            'filter'    => '\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter\Category',
            'frame_callback' => [$this, 'callbackColumnCategory'],
            'filter_condition_callback' => [$this, 'callbackFilterCategory']
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'field' => 'id',
            'group_order' => $this->getGroupOrder(),
            'actions'     => $this->getColumnActionsItems()
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
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

    protected function _prepareMassactionGroup()
    {
        $this->getMassactionBlock()->setGroups([
            'edit_settings'            => $this->__('Edit Listing Policies Overrides'),
            'edit_categories_settings' => $this->__('Edit eBay Categories'),
            'other'                    => $this->__('Other')
        ]);

        return $this;
    }

    protected function _prepareMassactionItems()
    {
        // --- Payment and Shipping Settings -----

        $this->getMassactionBlock()->addItem('editPaymentSettings', [
            'label'    => $this->__('Payment'),
            'url'      => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('editShippingSettings', [
            'label'    => $this->__('Shipping'),
            'url'      => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('editReturnSettings', [
            'label'    => $this->__('Return'),
            'url'      => '',
        ], 'edit_settings');

        // ---------------------------------------

        // ---------- Selling Settings -----------

        $this->getMassactionBlock()->addItem('editPriceQuantityFormatSettings', [
            'label'    => $this->__('Price, Quantity and Format'),
            'url'      => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('editDescriptionSettings', [
            'label'    => $this->__('Description'),
            'url'      => '',
        ], 'edit_settings');

        // ---------------------------------------

        // ---------- Synchronization ------------

        $this->getMassactionBlock()->addItem('editSynchSettings', [
            'label'    => $this->__('Synchronization'),
            'url'      => '',
        ], 'edit_settings');

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('editCategorySettings', [
            'label'    => $this->__('All Categories'),
            'url'      => '',
        ], 'edit_categories_settings');

        $this->getMassactionBlock()->addItem('editPrimaryCategorySettings', [
                'label'    => $this->__('eBay Catalog Primary Categories'),
                'url'      => '',
        ], 'edit_categories_settings');

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $this->getMassactionBlock()->addItem('editStorePrimaryCategorySettings', [
                'label'    => $this->__('Store Catalog Primary Categories'),
                'url'      => '',
            ], 'edit_categories_settings');
        }

        // ---------- Other ------------

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $this->getMassactionBlock()->addItem('editMotors', [
                'label'    => $this->__('Add Compatible Vehicles'),
                'url'      => '',
                'confirm'  => $this->__('Are you sure?')
            ], 'other');
        }

        $this->getMassactionBlock()->addItem('moving', [
            'label'    => $this->__('Move Item(s) to Another Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'other');

        return $this;
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<span>'.$this->getHelper('Data')->escapeHtml($value).'</span>';

        $sku = $row->getData('sku');
        if (is_null($sku)) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU') . ':</strong>&nbsp;';
        $value .= $this->getHelper('Data')->escapeHtml($sku);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $row->getData('listing_product_id'));

        if ($listingProduct->getChildObject()->isVariationsReady()) {
            $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

            $productAttributes = isset($additionalData['variations_sets'])
                ? array_keys($additionalData['variations_sets']) : array();

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">';
            $value .= implode(', ', $productAttributes);
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnCategory($value, $row, $column, $isExport)
    {
        $value = '';

        $categories = $this->getHelper('Component\Ebay\Category')->getCategoryTitles();

        if ($row->getData('category_main_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            $value .= $this->getCategoryInfoHtml(
                $this->getHelper('Component\Ebay\Category')->getCategoryTitle(
                    \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN
                ),
                '<span style="color: red">'.$this->__('Not Set').'</span>'
            );
        } else {
            $value .= $this->getEbayCategoryInfoHtml($row,'category_main',
                $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN]);
        }

        $value .= $this->getEbayCategoryInfoHtml($row,'category_secondary',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY]);

        $value .= $this->getStoreCategoryInfoHtml($row,'category_main',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN]);
        $value .= $this->getStoreCategoryInfoHtml($row,'category_secondary',
            $categories[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY]);
        $value .= '<br/>';

        $templatesNames = [
            Manager::TEMPLATE_PAYMENT => $this->__('Payment'),
            Manager::TEMPLATE_SHIPPING => $this->__('Shipping'),
            Manager::TEMPLATE_RETURN_POLICY => $this->__('Return'),
            Manager::TEMPLATE_SELLING_FORMAT => $this->__('Price, Quantity and Format'),
            Manager::TEMPLATE_DESCRIPTION => $this->__('Description'),
            Manager::TEMPLATE_SYNCHRONIZATION => $this->__('Synchronization'),
        ];

        $productTemplatesHtml = '';
        foreach ($templatesNames as $templateNick => $templateTitle) {

            $templateMode = $row->getData('template_' .$templateNick . '_mode');

            if ($templateMode == Manager::MODE_PARENT) {
                continue;
            }

            $templateLink = '';
            if ($templateMode == Manager::MODE_CUSTOM) {

                $templateLink = '<span>' . $this->__('Custom Settings') . '</span>';

            } else if ($templateMode == Manager::MODE_TEMPLATE) {

                $id = (int)$row->getData('template_' .$templateNick. '_id');
                $url = $this->getUrl('m2epro/ebay_template/edit', [
                    'id' => $id,
                    'nick' => $templateNick
                ]);
                $objTitle = $this->templateManager->setTemplate($templateNick)
                                                       ->getTemplateModel()
                                                       ->load($id)
                                                       ->getTitle();
                $templateLink = '<a href="'.$url.'" target="_blank">'
                                . $objTitle
                                . '</a>';
            }

            $productTemplatesHtml .= "<div style='padding: 2px 0 0 10px'>
                                    <strong>{$templateTitle}:</strong>
                                    <span style='padding: 0 10px 0 5px'>{$templateLink}</span>
                                   </div>";
        }

        if (!empty($productTemplatesHtml)) {
            $value .= "<div class='product_templates' style='text-decoration: underline;'>
                        {$this->__('Listing Policies Overrides')}
                       </div>"
                   . $productTemplatesHtml;
        }

        return $value;
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

        $showAll = false;

        if ($countOfItems + $countOfFilters + $countOfGroups === 0) {
            $showAll = true;
        }

        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            $motorsTypeTitle = 'ePIDs';
        } else {
            $motorsTypeTitle = 'kTypes';
        }

        $html = '<div style="padding: 4px; color: #666666">';
        $labelFilters = $this->__('Filters');
        $labelGroups = $this->__('Groups');

        if ($showAll || $countOfItems > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$motorsTypeTitle}</span>:&nbsp;
HTML;

            if ($countOfItems > 0) {
                $html .= <<<HTML
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewItemPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfItems}</a><br/>
HTML;
            } else {
                $html .= <<<HTML
    <span>0</span><br/>
HTML;
            }
        }

        if ($showAll || $countOfFilters > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$labelFilters}</span>:&nbsp;
HTML;

            if ($countOfFilters) {
                $html .= <<<HTML
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewFilterPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfFilters}</a><br/>
HTML;
            } else {
                $html .= <<<HTML
    <span>0</span><br/>
HTML;
            }
        }

        if ($showAll || $countOfGroups > 0) {
            $html .= <<<HTML
<span style="text-decoration: underline;">{$labelGroups}</span>:&nbsp;
HTML;

            if ($countOfGroups) {
                $html .= <<<HTML
<a href="javascript:void(0);"
    onclick="EbayListingViewSettingsMotorsObj.openViewGroupPopup(
        {$row->getData('id')},
        EbayListingViewSettingsGridObj
    );">{$countOfGroups}</a>
HTML;
            } else {
                $html .= <<<HTML
    <span>0</span><br/>
HTML;
            }
        }

        $html .= '</div>';

        return $html;
    }

    //########################################

    public function callbackFilterTitle($collection, $column)
    {
        if (!is_null($inputValue = $column->getFilter()->getValue())) {

            $fieldsToFilter = [
                ['attribute'=>'sku','like'=>'%'.$inputValue.'%'],
                ['attribute'=>'name','like'=>'%'.$inputValue.'%']
            ];

            $collection->addFieldToFilter($fieldsToFilter);
        }
    }

    public function callbackFilterCategory($collection, $column)
    {
        if (!is_null($inputValue = $column->getFilter()->getValue('input'))) {

            $fieldsToFilter = [
                ['attribute'=>'category_main_path','like'=>'%'.$inputValue.'%'],
                ['attribute'=>'category_secondary_path','like'=>'%'.$inputValue.'%'],
                ['attribute'=>'store_category_main_path','like'=>'%'.$inputValue.'%'],
                ['attribute'=>'store_category_secondary_path','like'=>'%'.$inputValue.'%'],
            ];

            if (is_numeric($inputValue)) {
                $fieldsToFilter[] = ['attribute'=>'category_main_id','eq'=>$inputValue];
                $fieldsToFilter[] = ['attribute'=>'category_secondary_id','eq'=>$inputValue];
                $fieldsToFilter[] = ['attribute'=>'store_category_main_id','eq'=>$inputValue];
                $fieldsToFilter[] = ['attribute'=>'store_category_secondary_id','eq'=>$inputValue];
            }

            $collection->addFieldToFilter($fieldsToFilter);
        }

        if (!is_null($selectValue = $column->getFilter()->getValue('select'))) {
            $collection->addFieldToFilter('template_category_id', [($selectValue ? 'notnull' : 'null') => true]);
        }

        if (!is_null($column->getFilter()->getValue('checkbox'))) {
            $allTemplates = $this->templateManager->getAllTemplates();

            foreach ($allTemplates as $templateNick) {
                $collection->getSelect()->orWhere('elp.template_'.$templateNick.'_mode > 0');
            }
        }
    }

    public function callbackFilterMotorsAttribute($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (is_null($value)) {
            return;
        }

        if (!$this->motorsAttribute) {
            return;
        }

        $attributeCode = $this->motorsAttribute->getAttributeCode();

        if ($value == 1) {
            $collection->addFieldToFilter($attributeCode,array('notnull'=>true));
            $collection->addFieldToFilter($attributeCode,array('neq'=>''));
            $collection->addFieldToFilter(
                'is_motors_attribute_in_product_attribute_set',array('notnull'=>true)
            );
        } else {
            $collection->addFieldToFilter(
                [
                    ['attribute' => $attributeCode, 'null' => true],
                    ['attribute' => $attributeCode, 'eq' => ''],
                    ['attribute' => 'is_motors_attribute_in_product_attribute_set', 'null' => true]
                ]
            );
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing/view', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function isExistsListingSettingsOverwrites()
    {
        $listingProductCollection = $this->ebayFactory
            ->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('listing_id', $this->listing->getId());

        $allTemplates = $this->templateManager->getAllTemplates();

        $where = [];
        $conditions = [];
        foreach ($allTemplates as $templateNick) {
            $where[] = ['second_table','template_'.$templateNick.'_mode'];
            $conditions[] = ['gt' => 0];
        }
        $listingProductCollection->addFieldToFilter($where, $conditions);

        return $listingProductCollection->getSize();
    }

    //########################################

    private function getEbayCategoryInfoHtml($row, $modeNick, $modeTitle)
    {
        $helper = $this->getHelper('Data');
        $mode = $row->getData($modeNick.'_mode');

        if (is_null($mode) || $mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {

            $category = $this->__('Magento Attribute'). ' > ';
            $category.= $helper->escapeHtml(
                $this->getHelper('Magento\Attribute')->getAttributeLabel(
                    $row->getData($modeNick.'_attribute'),
                    $this->listing->getStoreId()
                )
            );

        } else {
            $category = $helper->escapeHtml($row->getData($modeNick.'_path')).' ('.$row->getData($modeNick.'_id').')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getStoreCategoryInfoHtml($row, $modeNick, $modeTitle)
    {
        $helper = $this->getHelper('Data');
        $mode = $row->getData('store_'.$modeNick.'_mode');

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return '';
        }

        if ($mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {

            $category = $this->__('Magento Attribute'). ' > ';
            $category .= $helper->escapeHtml(
                $this->getHelper('Magento\Attribute')->getAttributeLabel(
                    $row->getData('store_'.$modeNick.'_attribute'),
                    $this->listing->getStoreId()
                )
            );

        } else {
            $category = $helper->escapeHtml($row->getData('store_'.$modeNick.'_path')).
                        ' ('.$row->getData('store_'.$modeNick.'_id').')';
        }

        return $this->getCategoryInfoHtml($modeTitle, $category);
    }

    private function getCategoryInfoHtml($modeTitle, $category)
    {
        return <<<HTML
    <div>
        <span style="text-decoration: underline">{$modeTitle}</span>
        <p style="padding: 2px 0 0 10px">{$category}</p>
    </div>
HTML;
    }

    //########################################

    private function isMotorsAvailable()
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
            return $this->getHelper('Component\Ebay\Motors')->getEpidsTypeByMarketplace(
                $this->listing->getMarketplaceId()
            );
        }

        return \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
    }

    //########################################

    protected function getGroupOrder()
    {
        return [
            'edit_general_settings'    => $this->__('Edit Listing Policies Overrides'),
            'edit_categories_settings' => $this->__('Edit eBay Categories'),
            'other'                    => $this->__('Other')
        ];
    }

    protected function getColumnActionsItems()
    {
        $actions = [
            'editCategories' => [
                'caption' => $this->__('All Categories'),
                'group'   => 'edit_categories_settings',
                'field'   => 'id',
                'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editCategorySettingsAction\']'
            ],

            'editPrimaryCategories' => [
                'caption' => $this->__('eBay Catalog Category'),
                'group'   => 'edit_categories_settings',
                'field'   => 'id',
                'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editPrimaryCategorySettingsAction\']'
            ],
        ];

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $actions['editStorePrimaryCategories'] =  [
                'caption' => $this->__('Store Catalog Category'),
                'group'   => 'edit_categories_settings',
                'field'   => 'id',
                'onclick_action' => 'EbayListingViewSettingsGridObj.'
                                    .'actions[\'editStorePrimaryCategorySettingsAction\']'
            ];
        }

        // --- Payment and Shipping Settings -----

        $actions['editPayment'] = [
            'caption' => $this->__('Payment'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editPaymentSettingsAction\']'
        ];

        $actions['editShipping'] = [
            'caption' => $this->__('Shipping'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editShippingSettingsAction\']'
        ];

        $actions['editReturn'] = [
            'caption' => $this->__('Return'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editReturnSettingsAction\']'
        ];

        // ---------------------------------------

        // ---------- Selling Settings -----------
        $actions['priceQuantityFormat'] = [
            'caption' => $this->__('Price, Quantity and Format'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editPriceQuantityFormatSettingsAction\']'
        ];

        $actions['editDescription'] = [
            'caption' => $this->__('Description'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editDescriptionSettingsAction\']'
        ];

        // ---------------------------------------

        // ---------- Synchronization ------------

        $actions['editSynchSettings'] = [
            'caption' => $this->__('Synchronization'),
            'group'   => 'edit_general_settings',
            'field'   => 'id',
            'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editSynchSettingsAction\']'
        ];

        // ---------------------------------------

        // ---------- Other ------------

        if ($this->isMotorsAvailable() && $this->motorsAttribute) {
            $actions['editMotors'] = [
                'caption' => $this->__('Add Compatible Vehicles'),
                'group' => 'other',
                'field' => 'id',
                'onclick_action' => 'EbayListingViewSettingsGridObj.actions[\'editMotorsAction\']'
            ];
        }

        // ---------------------------------------

        return $actions;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $this->js->add(<<<JS
            EbayListingViewSettingsGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        /** @var $helper \Ess\M2ePro\Helper\Data */
        $helper = $this->getHelper('Data');

        // ---------------------------------------
        $this->jsPhp->addConstants($helper->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay\Category'));
        $this->jsPhp->addConstants($helper->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Manager'));
        // ---------------------------------------

        // ---------------------------------------
        $this->jsUrl->addUrls($helper->getControllerActions('Ebay\Listing', ['_current' => true]));
        $this->jsUrl->add($this->getUrl('*/ebay_listing/view'), 'ebay_listing/getTransferringUrl');

        $this->jsUrl->add(
            $this->getUrl('*/ebay_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId()
            ]),
            'ebay_log_listing_product/index'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId(),
                'back' => $helper->makeBackUrlParam(
                    '*/ebay_listing/view', ['id' => $this->listing->getId()]
                )
            ]),
            'logViewUrl'
        );

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_settings_moving/moveToListingGrid', ['listing_view' => true]),
            'moveToListingGridHtml'
        );
        $this->jsUrl->add($this->getUrl('*/listing_moving/prepareMoveToListing'), 'prepareData');
        $this->jsUrl->add($this->getUrl('*/listing_moving/getFailedProducts'), 'getFailedProductsHtml');
        $this->jsUrl->add($this->getUrl('*/listing_moving/tryToMoveToListing'), 'tryToMoveToListing');
        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListing'), 'moveToListing');

        $this->jsUrl->add(
            $this->getUrl('*/ebay_template/editListingProductsPolicy'),
            'ebay_template/editListingProductsPolicy'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_template/saveListingProductsPolicy'),
            'ebay_template/saveListingProductsPolicy'
        );

        $this->jsUrl->addUrls($helper->getControllerActions('Ebay\Listing\Settings\Motors'));
        // ---------------------------------------

        // M2ePro_TRANSLATIONS
        // %task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.
        $taskCompletedWarningMessage = '"%task_title%" Task has completed with warnings.'
            .' <a target="_blank" href="%url%">View Log</a> for details.';

        // M2ePro_TRANSLATIONS
        // "%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.
        $taskCompletedErrorMessage = '"%task_title%" Task has completed with errors. '
            .' <a target="_blank" href="%url%">View Log</a> for details.';

        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            $motorsTypeTitle = 'ePID';
        } else {
            $motorsTypeTitle = 'kType';
        }

        //------------------------------
        $this->jsTranslator->addTranslations([
            'Edit Return Policy Setting' => $this->__('Edit Return Policy Setting'),
            'Edit Payment Policy Setting' => $this->__('Edit Payment Policy Setting'),
            'Edit Shipping Policy Setting' => $this->__('Edit Shipping Policy Setting'),
            'Edit Description Policy Setting' => $this->__('Edit Description Policy Setting'),
            'Edit Price, Quantity and Format Policy Setting' =>
                $this->__('Edit Price, Quantity and Format Policy Setting'),
            'Edit Synchronization Policy Setting' => $this->__('Edit Synchronization Policy Setting'),
            'Edit Settings' => $this->__('Edit Settings'),
            'for' => $this->__('for'),
            'eBay Categories' => $this->__('eBay Categories'),
            'of Product' => $this->__('of Product'),
            'Specifics' => $this->__('Specifics'),
            'Compatibility Attribute ePIDs' => $this->__('Compatibility Attribute ePIDs'),
            'Payment for Translation Service' => $this->__('Payment for Translation Service'),
            'Payment for Translation Service. Help' => $this->__('Payment for Translation Service'),
            'Specify a sum to be credited to an Account.' =>
                $this->__('Specify a sum to be credited to an Account.'
                           .' If you are planning to order more Items for Translation in future,'
                           .' you can credit the sum greater than the one needed for current Translation.'
                           .' Click <a href="%url%" target="_blank">here</a> to find out more.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                    'x/BQAJAQ#SellonanothereBaySite-Account')
                ),
            'Amount to Pay.' => $this->__('Amount to Pay'),
            'Insert amount to be credited to an Account' => $this->__('Insert amount to be credited to an Account.'),
            'Confirm' => $this->__('Confirm'),
            'Add Compatible Vehicles' => $this->__('Add Compatible Vehicles'),
            'Save Filter' => $this->__('Save Filter'),
            'Save as Group' => $this->__('Save as Group'),
            'Set Note' => $this->__('Set Note'),
            'View Items' => $this->__('Selected %items_title%s', $motorsTypeTitle),
            'Selected Items' => $this->__('Selected %items_title%s',$motorsTypeTitle),
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
            'task_completed_success_message' => $this->__('"%task_title%" Task has successfully completed.'),
            'sending_data_message' => $this->__('Sending %product_title% Product(s) data on eBay.'),
            'View Full Product Log.' => $this->__('View Full Product Log.'),
            'The Listing was locked by another process. Please try again later.' =>
                $this->__('The Listing was locked by another process. Please try again later.'),
            'Listing is empty.' => $this->__('Listing is empty.'),
            'Please select Items.' => $this->__('Please select Items.'),
            'Please select Action.' => $this->__('Please select Action.'),
            'popup_title' => $this->__('Moving eBay Items'),
            'popup_title_single' => $this->__('Moving eBay Item'),
            'successfully_moved' => $this->__('Product(s) was successfully Moved.'),
            'failed_products_popup_title' => $this->__('Product(s) failed to move'),
            'products_were_not_moved' => $this->__(
                'Product(s) was not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
            ),
            'some_products_were_not_moved' => $this->__(
                'Some Product(s) was not Moved. <a target="_blank" href="%url%">View Log</a> for details.'
            ),
            'task_completed_warning_message' => $this->__($taskCompletedWarningMessage),
            'task_completed_error_message' => $this->__($taskCompletedErrorMessage),
            'Add New Listing' => $this->__('Add New Listing')
        ]);

        $temp = $this->getHelper('Data\Session')->getValue('products_ids_for_list',true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;
        $ignoreListings = $this->getHelper('Data')->jsonEncode([$this->listing->getId()]);

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
        'M2ePro/Ebay/Listing/View/Settings/Motors'
    ], function(){

        window.EbayListingViewSettingsGridObj = new EbayListingViewSettingsGrid(
            '{$this->getId()}',
            '{$this->listing->getId()}'
        );
        EbayListingViewSettingsGridObj.afterInitPage();
        EbayListingViewSettingsGridObj.movingHandler.setOptions(M2ePro);

        EbayListingViewSettingsMotorsObj = new EbayListingViewSettingsMotors({$this->listing->getId()},'{$motorsType}');
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

    //########################################

    private function prepareExistingMotorsData()
    {
        $motorsHelper = $this->getHelper('Component\Ebay\Motors');

        $products = $this->getCollection()->getItems();

        $productsMotorsData = array();

        $items = array();
        $filters = array();
        $groups = array();

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
                array($typeIdentifier)
            )
            ->where('`'.$typeIdentifier.'` IN (?)', $items);

        if ($motorsHelper->isTypeBasedOnEpids($this->getMotorsType())) {
            $select->where('scope = ?', $motorsHelper->getEpidsScopeByType($this->getMotorsType()));
        }

        $existedItems = $select->query()->fetchAll(\PDO::FETCH_COLUMN);
        //-------------------------------

        //-------------------------------
        $filtersTable = $this->resourceConnection->getTableName('m2epro_ebay_motor_filter');
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $filtersTable,
                array('id')
            )
            ->where('`id` IN (?)', $filters);

        $existedFilters = $select->query()->fetchAll(\PDO::FETCH_COLUMN);
        //-------------------------------

        //-------------------------------
        $groupsTable = $this->resourceConnection->getTableName('m2epro_ebay_motor_group');
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $groupsTable,
                array('id')
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

    //########################################
}