<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Settings;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewSettingsGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setListing($this->listing->getId());

        if ($this->isFilterOrSortByPriceIsUsed(null, 'amazon_online_price')) {
            $collection->setIsNeedToUseIndexerParent(true);
        }

        $collection->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem();

        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array(
                'id'              => 'id',
                'amazon_status'   => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ),
            array(
                'listing_id' => (int)$this->listing['id']
            )
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('alp' => $alpTable),
            'listing_product_id=id',
            array(
                'template_shipping_template_id'  => 'template_shipping_template_id',
                'template_shipping_override_id'  => 'template_shipping_override_id',
                'template_description_id'        => 'template_description_id',
                'template_product_tax_code_id'   => 'template_product_tax_code_id',
                'general_id'                     => 'general_id',
                'general_id_search_info'         => 'general_id_search_info',
                'search_settings_status'         => 'search_settings_status',
                'search_settings_data'           => 'search_settings_data',
                'variation_child_statuses'       => 'variation_child_statuses',
                'amazon_sku'                     => 'sku',
                'online_qty'                     => 'online_qty',
                'online_regular_price'           => 'online_regular_price',
                 'online_regular_sale_price'      => 'IF(
                  `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                  `alp`.`online_regular_sale_price`,
                  NULL
                )',
                'online_regular_sale_price_start_date'   => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date'     => 'online_regular_sale_price_end_date',
                'is_afn_channel'                   => 'is_afn_channel',
                'is_repricing'                     => 'is_repricing',
                'is_general_id_owner'              => 'is_general_id_owner',
                'is_variation_parent'              => 'is_variation_parent',
                'variation_parent_afn_state'       => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',
                'defected_messages'                => 'defected_messages'
            ),
            '{{table}}.variation_parent_id is NULL'
        );

        $tdTable = $this->activeRecordFactory->getObject('Template\Description')->getResource()->getMainTable();
        $collection->joinTable(
            array('td' => $tdTable),
            'id=template_description_id',
            array(
                'template_description_title' => 'title'
            ),
            null,
            'left'
        );

        $atsTable = $this->activeRecordFactory->getObject('Amazon\Template\ShippingOverride')
            ->getResource()->getMainTable();
        $collection->joinTable(
            array('atso' => $atsTable),
            'id=template_shipping_override_id',
            array(
                'template_shipping_override_title' => 'title'
            ),
            null,
            'left'
        );

        $tsTable = $this->activeRecordFactory->getObject('Amazon\Template\ShippingTemplate')
            ->getResource()->getMainTable();
        $collection->joinTable(
            array('ts' => $tsTable),
            'id=template_shipping_template_id',
            array(
                'template_shipping_template_title' => 'title'
            ),
            null,
            'left'
        );

        $amazonAccount = $this->listing->getAccount()->getChildObject();

        if ($amazonAccount->getMarketplace()->getChildObject()->isProductTaxCodePolicyAvailable() &&
            $amazonAccount->isVatCalculationServiceEnabled()
        ) {
            $ptcTable = $this->activeRecordFactory->getObject('Amazon\Template\ProductTaxCode')
                ->getResource()->getMainTable();
            $collection->joinTable(
                array('tptc' => $ptcTable),
                'id=template_product_tax_code_id',
                array(
                    'template_product_tax_code_title' => 'title'
                ),
                null,
                'left'
            );
        }

        if ($collection->isNeedUseIndexerParent()) {
            $collection->joinIndexerParent();
        }

        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'amazon_sku',
            'filter_index' => 'amazon_sku',
            'frame_callback' => array($this, 'callbackColumnAmazonSku')
        ));

        $this->addColumn('general_id', array(
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '140px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        $this->addColumn('description_template', array(
            'header' => $this->__('Description Policy'),
            'align' => 'left',
            'width' => '170px',
            'type' => 'text',
            'index' => 'template_description_title',
            'filter_index' => 'template_description_title',
            'frame_callback' => array($this, 'callbackColumnTemplateDescription')
        ));

        $indexField = 'template_shipping_override_title';
        $title = $this->__('Shipping Override Policy');

        if ($this->listing->getAccount()->getChildObject()->isShippingModeTemplate()) {
            $indexField = 'template_shipping_template_title';
            $title = $this->__('Shipping Template Policy');
        }

        $this->addColumn('shipping_override_template', array(
            'header' => $title,
            'align' => 'left',
            'width' => '170px',
            'type' => 'text',
            'index' => $indexField,
            'filter_index' => $indexField,
            'frame_callback' => array($this, 'callbackColumnTemplateShipping')
        ));

        if ($this->listing->getMarketplace()->getChildObject()->isProductTaxCodePolicyAvailable() &&
            $this->listing->getAccount()->getChildObject()->isVatCalculationServiceEnabled()
        ) {
            $this->addColumn('product_tax_code_template', array(
                'header' => $this->__('Product Tax Code Policy'),
                'align' => 'left',
                'width' => '170px',
                'type' => 'text',
                'index' => 'template_product_tax_code_title',
                'filter_index' => 'template_product_tax_code_title',
                'frame_callback' => array($this, 'callbackColumnTemplateProductTaxCode')
            ));
        }

        $this->addColumn('actions', array(
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'field' => 'id',
            'group_order' => $this->getGroupOrder(),
            'actions'     => $this->getColumnActionsItems()
        ));

        return parent::_prepareColumns();
    }

    //########################################

    protected function getGroupOrder()
    {
        $groups = array(
            'edit_template_description' => $this->__('Description Policy'),
            'edit_template_shipping'    => $this->__('Shipping Override Policy')
        );

        if ($this->listing->getAccount()->getChildObject()->isShippingModeTemplate()) {
            $groups['edit_template_shipping'] = $this->__('Shipping Template Policy');
        }

        if ($this->listing->getMarketplace()->getChildObject()->isProductTaxCodePolicyAvailable() &&
            $this->listing->getAccount()->getChildObject()->isVatCalculationServiceEnabled()
        ) {
            $groups['edit_template_product_tax_code'] = $this->__('Product Tax Code Policy');
        }

        return $groups;
    }

    protected function getColumnActionsItems()
    {
        $actions = array(
            'assignTemplateDescription' => array(
                'caption' => $this->__('Assign'),
                'group'   => 'edit_template_description',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.actions[\'assignTemplateDescriptionIdAction\']'
            ),

            'unassignTemplateDescription' => array(
                'caption' => $this->__('Unassign'),
                'group'   => 'edit_template_description',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.unassignTemplateDescriptionIdActionConfrim'
            ),
        );

        if ($this->listing->getAccount()->getChildObject()->isShippingModeTemplate()) {

            $actions['assignTemplateShipping'] = array(
                'caption' => $this->__('Assign'),
                'group'   => 'edit_template_shipping',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.actions[\'assignTemplateShippingTemplateIdAction\']'
            );

            $actions['unassignTemplateShipping'] = array(
                'caption' => $this->__('Unassign'),
                'group'   => 'edit_template_shipping',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.unassignTemplateShippingTemplateIdActionConfrim'
            );
        }

        if ($this->listing->getAccount()->getChildObject()->isShippingModeOverride()) {

            $actions['assignTemplateShippingOverride'] = array(
                'caption' => $this->__('Assign'),
                'group'   => 'edit_template_shipping',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.actions[\'assignTemplateShippingOverrideIdAction\']'
            );

            $actions['unassignTemplateShippingOverride'] = array(
                'caption' => $this->__('Unassign'),
                'group'   => 'edit_template_shipping',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.unassignTemplateShippingOverrideIdActionConfrim'
            );
        }

        if ($this->listing->getMarketplace()->getChildObject()->isProductTaxCodePolicyAvailable() &&
            $this->listing->getAccount()->getChildObject()->isVatCalculationServiceEnabled()
        ) {
            $actions['assignTemplateProductTaxCode'] = array(
                'caption' => $this->__('Assign'),
                'group'   => 'edit_template_product_tax_code',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.actions[\'assignTemplateProductTaxCodeIdAction\']'
            );

            $actions['unassignTemplateProductTaxCode'] = array(
                'caption' => $this->__('Unassign'),
                'group'   => 'edit_template_product_tax_code',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.unassignTemplateProductTaxCodeIdActionConfrim'
            );
        }

        return $actions;
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
        $groups = array(
            'description_policy' => $this->__('Description Policy'),
            'shipping_policy' => $this->__('Shipping Override Policy'),
            'edit_template_product_tax_code' => $this->__('Product Tax Code Policy'),
            'other'              => $this->__('Other'),
        );

        if ($this->listing->getAccount()->getChildObject()->isShippingModeTemplate()) {
            $groups['shipping_policy'] = $this->__('Shipping Template Policy');
        }

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('assignTemplateDescriptionId', array(
            'label'    => $this->__('Assign'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'description_policy');

        $this->getMassactionBlock()->addItem('unassignTemplateDescriptionId', array(
            'label'    => $this->__('Unassign'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'description_policy');

        if ($this->listing->getAccount()->getChildObject()->isShippingModeTemplate()) {

            $this->getMassactionBlock()->addItem('assignTemplateShippingTemplateId', array(
                'label'   => $this->__('Assign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'shipping_policy');

            $this->getMassactionBlock()->addItem('unassignTemplateShippingTemplateId', array(
                'label'   => $this->__('Unassign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'shipping_policy');
        }

        if ($this->listing->getAccount()->getChildObject()->isShippingModeOverride()) {

            $this->getMassactionBlock()->addItem('assignTemplateShippingOverrideId', array(
                'label'   => $this->__('Assign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'shipping_policy');

            $this->getMassactionBlock()->addItem('unassignTemplateShippingOverrideId', array(
                'label'   => $this->__('Unassign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'shipping_policy');
        }

        if ($this->listing->getMarketplace()->getChildObject()->isProductTaxCodePolicyAvailable() &&
            $this->listing->getAccount()->getChildObject()->isVatCalculationServiceEnabled()
        ) {
            $this->getMassactionBlock()->addItem('assignTemplateProductTaxCodeId', array(
                'label'   => $this->__('Assign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_template_product_tax_code');

            $this->getMassactionBlock()->addItem('unassignTemplateProductTaxCodeId', array(
                'label'   => $this->__('Unassign'),
                'url'     => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_template_product_tax_code');
        }

        $this->getMassactionBlock()->addItem('moving', array(
            'label'    => $this->__('Move Item(s) to Another Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'other');

        $this->getMassactionBlock()->addItem('duplicate', array(
            'label'    => $this->__('Duplicate'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'other');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        if (is_null($sku = $row->getData('sku'))) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if (!$listingProduct->getChildObject()->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {

            $productAttributes = (array)$variationManager->getTypeModel()->getProductAttributes();
            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $attributesStr = '';
            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {

                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';

                    } else if (in_array($attribute, array_keys($virtualChannelAttributes))) {

                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';

                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }
            $value .= $attributesStr;

            return $value;
        }

        $productOptions = $variationManager->getTypeModel()->getProductOptions();

        if (!empty($productOptions)) {
            $value .= '<div style="font-size: 11px; color: grey; margin-left: 7px"><br/>';
            foreach ($productOptions as $attribute => $option) {
                !$option && $option = '--';
                $value .= '<strong>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnAmazonSku($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        }

        return $value;
    }

    // ---------------------------------------

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        if (empty($generalId)) {
            if ($row->getData('is_general_id_owner') == 1) {
                return $this->__('New ASIN/ISBN');
            }
            return $this->getGeneralIdColumnValueEmptyGeneralId($row);
        }

        return $this->getGeneralIdColumnValueNotEmptyGeneralId($row);
    }

    private function getGeneralIdColumnValueEmptyGeneralId($row)
    {
        // ---------------------------------------
        if ((int)$row->getData('amazon_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<i style="color:gray;">'.$this->__('receiving...').'</i>';
        }

        $iconPath = $this->getSkinUrl('M2ePro/images/search_statuses/');
        $searchSettingsStatus = $row->getData('search_settings_status');

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS) {

            $tip = $this->__('Automatic ASIN/ISBN Search in Progress.');
            $iconSrc = $iconPath.'processing.gif';

            return <<<HTML
&nbsp;
<a href="javascript: void(0);" title="{$tip}">
    <img src="{$iconSrc}" alt="">
</a>
HTML;
        }
        // ---------------------------------------

        return $this->__('N/A');
    }

    private function getGeneralIdColumnValueNotEmptyGeneralId($row)
    {
        $generalId = $row->getData('general_id');

        $url = $this->getHelper('Component\Amazon')->getItemUrl($generalId, $this->listing->getMarketplaceId());

        $generalIdOwnerHtml = '';
        if ($row->getData('is_general_id_owner') == \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {

            $generalIdOwnerHtml = '<br/><span style="font-size: 10px; color: grey;">'.
                $this->__('creator of ASIN/ISBN').
                '</span>';
        }

        if ((int)$row->getData('amazon_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {

            return <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>{$generalIdOwnerHtml}
HTML;
        }

        $generalIdSearchInfo = $row->getData('general_id_search_info');

        if (!empty($generalIdSearchInfo)) {
            $generalIdSearchInfo = $this->getHelper('Data')->jsonDecode($generalIdSearchInfo);
        }

        if (!empty($generalIdSearchInfo['is_set_automatic'])) {

            $tip = $this->__('ASIN/ISBN was found automatically');

            $text = <<<HTML
<a href="{$url}" target="_blank" title="{$tip}" style="color:#40AADB;">{$generalId}</a>
HTML;

        } else {

            $text = <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>
HTML;

        }

        return $text . $generalIdOwnerHtml;
    }

    // ---------------------------------------

    public function callbackColumnTemplateDescription($value, $row, $column, $isExport)
    {
        $html = $this->__('N/A');

        if ($row->getData('template_description_id')) {

            $url = $this->getUrl('*/amazon_template_description/edit', array(
                'id' => $row->getData('template_description_id'),
                'close_on_save' => true
            ));

            $templateTitle = $this->getHelper('Data')->escapeHtml($row->getData('template_description_title'));

            return <<<HTML
<a target="_blank" href="{$url}">{$templateTitle}</a>
HTML;
        }

        return $html;
    }

    public function callbackColumnTemplateShipping($value, $row, $column, $isExport)
    {
        $html = $this->__('N/A');

        if (
            $this->listing->getAccount()->getChildObject()->isShippingModeOverride()
            && $row->getData('template_shipping_override_id')
        ) {

            $url = $this->getUrl('*/amazon_template_shippingOverride/edit', array(
                'id' => $row->getData('template_shipping_override_id'),
                'close_on_save' => true
            ));

            $templateTitle = $this->getHelper('Data')->escapeHtml($row->getData('template_shipping_override_title'));

            return <<<HTML
<a target="_blank" href="{$url}">{$templateTitle}</a>
HTML;
        }

        if (
            $this->listing->getAccount()->getChildObject()->isShippingModeTemplate()
            && $row->getData('template_shipping_template_id')
        ) {

            $url = $this->getUrl('*/amazon_template_shippingTemplate/edit', array(
                'id' => $row->getData('template_shipping_template_id'),
                'close_on_save' => true
            ));

            $templateTitle = $this->getHelper('Data')->escapeHtml($row->getData('template_shipping_template_title'));

            return <<<HTML
<a target="_blank" href="{$url}">{$templateTitle}</a>
HTML;
        }

        return $html;
    }

    public function callbackColumnTemplateProductTaxCode($value, $row, $column, $isExport)
    {
        $html = $this->__('N/A');

        if ($row->getData('template_product_tax_code_id')) {

            $url = $this->getUrl('*/amazon_template_productTaxCode/edit', array(
                'id' => $row->getData('template_product_tax_code_id')
            ));

            $templateTitle = $this->getHelper('Data')->escapeHtml($row->getData('template_product_tax_code_title'));

            return <<<HTML
<a target="_blank" href="{$url}">{$templateTitle}</a>
HTML;
        }

        return $html;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            array(
                array('attribute'=>'sku','like'=>'%'.$value.'%'),
                array('attribute'=>'name', 'like'=>'%'.$value.'%')
            )
        );
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
<<<JS
    ListingGridHandlerObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################
}