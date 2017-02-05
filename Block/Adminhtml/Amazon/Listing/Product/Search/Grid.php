<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $productId;
    private $currency;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct  */
    private $listingProduct;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $matcherAttributes */
    private $matcherAttributes;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option $matcherOptions */
    private $matcherOptions;

    protected $customCollectionFactory;
    protected $resourceConnection;
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->localeCurrency = $localeCurrency;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        $this->productId = $this->getHelper('Data\GlobalData')->getValue('product_id');
        $this->listingProduct = $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product', $this->productId
        );

        $this->matcherAttributes = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Attribute');
        $this->matcherOptions = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Option');

        $this->currency = $this->parentFactory
            ->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Marketplace',
                $this->getHelper('Data\GlobalData')->getValue('marketplace_id')
            )
            ->getChildObject()
            ->getDefaultCurrency();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonProductSearchGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $data = $this->getHelper('Data\GlobalData')->getValue('search_data');

        $collection = $this->customCollectionFactory->create();
        $collection->setConnection($this->resourceConnection->getConnection());

        foreach ($data['data'] as $index => $item) {
            $temp = array(
                'id' => $index,
                'general_id' => $item['general_id'],
                'brand' => $item['brand'],
                'title' => $item['title'],
                'image_url' => $item['image_url'],
                'price' => isset($item['list_price']['amount']) ? $item['list_price']['amount'] : null,
                'is_variation_product' => $item['is_variation_product']
            );

            if ($temp['is_variation_product']) {
                if (!$item['bad_parent']) {
                    $temp += array(
                        'parentage' => $item['parentage'],
                        'variations' => $item['variations'],
                        'bad_parent' => $item['bad_parent']
                    );
                } else {
                    $temp['bad_parent'] = $item['bad_parent'];
                }

                if (!empty($item['requested_child_id'])) {
                    $temp['requested_child_id'] = $item['requested_child_id'];
                }
            }

            $collection->addItem(new \Magento\Framework\DataObject($temp));
        }

        $collection->setCustomSize(count($data['data']));
        $this->setCollection($collection);

        parent::_prepareCollection();

        $collection->setCustomIsLoaded(true);

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('image', array(
            'header'       => $this->__('Image'),
            'align'        => 'center',
            'type'         => 'text',
            'width'        => '80px',
            'index'        => 'image_url',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnImage')
        ));

        $this->addColumn('title', array(
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'string_limit' => 10000,
            'width'        => '375px',
            'index'        => 'title',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnTitle'),
        ));

        $this->addColumn('price',array(
            'header'       => $this->__('Price'),
            'width'        => '60px',
            'align'        => 'right',
            'index'        => 'price',
            'filter'       => false,
            'sortable'     => false,
            'type'         => 'number',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('general_id', array(
            'header'       => $this->__('ASIN / ISBN'),
            'align'        => 'center',
            'type'         => 'text',
            'width'        => '75px',
            'index'        => 'general_id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        $this->addColumn('actions', array(
            'header'       => $this->__('Action'),
            'align'        => 'center',
            'type'         => 'text',
            'width'        => '78px',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnActions'),
        ));

    }

    //########################################

    public function callbackColumnImage($value, $product, $column, $isExport)
    {
        return '<img src="'.$value.'" />';
    }

    public function callbackColumnGeneralId($value, $product, $column, $isExport)
    {
        $url = $this->getHelper('Component\Amazon')
            ->getItemUrl($value, $this->getHelper('Data\GlobalData')->getValue('marketplace_id'));

        $parentAsinText = $this->__('parent ASIN/ISBN');

        return <<<HTML
<a id="asin_link_{$product->getData('id')}" href="{$url}" target="_blank">{$value}</a>
<div id="parent_asin_text_{$product->getData('id')}" style="font-size: 9px; color: grey; display: none">
    {$parentAsinText}
</div>
HTML;

    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px; margin-bottom: 3px;">'.
                        $this->getHelper('Data')->escapeHtml($value)."</div>";

        $id = $row->getId();
        $generalId = $row->getData('general_id');
        $categoryLinkTitle = $this->getHelper('Data')->escapeHtml('Show Categories');
        $notFoundText = $this->__('Categories Not Found');

        $value .= <<<HTML
<div style="margin-left: 3px; margin-bottom: 10px; font-size:10px; line-height: 1.1em">
    <a href="javascript:void(0)"
        onclick="ListingGridHandlerObj.productSearchHandler.showAsinCategories(
            this, {$id}, '{$generalId}', {$this->productId})">
        {$categoryLinkTitle}
    </a>
    <div id="asin_categories_{$id}"></div>
    <div id="asin_categories_not_found_{$id}" style="display: none; font-style: italic">{$notFoundText}</div>
</div>
HTML;

        if (!$this->listingProduct->getChildObject()->getVariationManager()->isVariationProduct()
            || $this->listingProduct->getChildObject()->getVariationManager()->isIndividualType()) {
            if (!$row->getData('is_variation_product')) {
                return $value;
            }
        } else {
            if (!$row->getData('is_variation_product')) {
                return $value;
            }
        }

        if ($row->getData('is_variation_product') && $row->getData('bad_parent')) {
            return $value;
        }

        $variations = $row->getData('variations');

        if ($this->listingProduct->getChildObject()->getVariationManager()->isRelationParentType()) {

            $magentoProductAttributesHtml = '';
            $magentoProductAttributesJs = '';

            $destinationAttributes = array_keys($variations['set']);

            $this->matcherAttributes->setMagentoProduct($this->listingProduct->getMagentoProduct());
            $this->matcherAttributes->setDestinationAttributes($destinationAttributes);

            if ($this->matcherAttributes->isAmountEqual()) {
                $magentoProductAttributesJs .= '<script type="text/javascript">';
                $magentoProductAttributesHtml .= '<div style="margin-bottom: 5px;"><span style="margin-left: 10px;
                                        font-size: 11px;
                                        font-weight: bold;
                                        color: #808080;
                                        display: inline-block;
                                        width: 250px;">' .
                    $this->__('Magento Attributes') .
                    '</span><span style="margin-left: 10px;
                                        font-size: 11px;
                                        font-weight: bold;
                                        color: #808080;
                                        display: inline-block;">' .
                    $this->__('Amazon Attributes') .
                    '</span></div>';

                $matchedAttributes = $this->matcherAttributes->getMatchedAttributes();
                $attributeId = 0;
                foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {

                    $magentoProductAttributesHtml .= '<span style="margin-left: 10px;
                                            font-size: 11px;
                                            color: #808080;
                                            display: inline-block;
                                            width: 250px;">'.
                        ucfirst(strtolower($magentoAttr)).
                        '</span>';
                    $magentoProductAttributesHtml .= '<input type="hidden" value="' .
                                       $this->getHelper('Data')->escapeHtml($magentoAttr) . '"
                                       id="magento_product_attribute_'.$attributeId.'_'.$id.'">';
                    $magentoProductAttributesHtml .=
<<<HTML
<select
    class="select admin__control-select amazon_product_attribute_{$id}"
    onchange="ListingGridHandlerObj.productSearchHandler.attributesChange(this)"
    style="width: 250px; margin-left: 10px; margin-bottom: 7px; font-size: 11px;
        background-position: calc(100% - 12px) -38px, 100%, calc(100% - 3.2rem) 0;"
    id="amazon_product_attribute_{$attributeId}_{$id}">
HTML;

                    if (!array_key_exists($amazonAttr,$variations['set']))
                    {
                        $magentoProductAttributesHtml .= '<option class="empty" value=""></option>';
                    }

                    foreach ($variations['set'] as $attrKey => $attrData) {

                        $selected = '';
                        if ($attrKey == $amazonAttr) {
                            $selected = 'selected';
                            $magentoProductAttributesJs .= <<<JS
ListingGridHandlerObj.productSearchHandler.attributesChange({id:"magento_product_attribute_{$magentoAttr}_{$id}"});
JS;
                        }

                        $attrKey = $this->getHelper('Data')->escapeHtml($attrKey);
                        $magentoProductAttributesHtml .= '<option value="'.$attrKey.'" '.$selected.'>'
                            .$attrKey.'</option>';
                    }
                    $magentoProductAttributesHtml .= '</select><br/>';
                    $attributeId++;
                }

                $magentoProductAttributesJs .= '</script>';

                $magentoProductAttributesHtml .= '<div id="variations_'.$id.'" style="display: none;">'.
                    $this->getHelper('Data')->jsonEncode($variations).
                    '</div>';
            } else {

                $matchedAttributes = json_encode($this->matcherAttributes->getMatchedAttributes(), JSON_FORCE_OBJECT);
                $destinationAttributes = $this->getHelper('Data')->jsonEncode($destinationAttributes);

                foreach ($variations['set'] as $attribute => $options) {
                    $variations['set'][$attribute] = array_values($options);
                }

                $amazonVariations = $this->getHelper('Data')->jsonEncode($variations);

                $magentoAttributesText = $this->__('Magento Attributes');
                $amazonAttributesText = $this->__('Amazon Attributes');

                $searchHandler = 'ListingGridHandlerObj.productSearchHandler';

                $value .= <<<HTML
<form id="matching_attributes_form_{$id}" action="javascript:void(0)" style="margin-left: 10px">
        <div class="matching-attributes-table" style="display:table;padding-left:10px;font-size: 11px;color: #808080;">
            <div class="matching-attributes-table-header"
                style="display: table-row; font-weight: bold; height: 20px;">
                <div style="display:table-cell; width: 250px;">
                    <span>{$magentoAttributesText}</span>
                </div>
                <div style="display:table-cell; padding-left: 10px;">
                    <span>{$amazonAttributesText}</span>
                </div>
            </div>
        </div>
    </form>
</div>
HTML;

                if ($this->matcherAttributes->isSourceAmountGreater()) {
                    $magentoProductVariationsSet = $this->listingProduct->getMagentoProduct()
                        ->getVariationInstance()->getVariationsTypeStandard();
                    $magentoProductVariationsSet = $this->getHelper('Data')->jsonEncode(
                        $magentoProductVariationsSet['set']
                    );
                    $productAttributes = $this->getHelper('Data')->jsonEncode(
                        $this->listingProduct->getChildObject()
                        ->getVariationManager()->getTypeModel()->getProductAttributes()
                    );

                    $value .= <<<HTML
<script type="application/javascript">
    {$searchHandler}.searchData[{$id}] = {};
    {$searchHandler}.searchData[{$id}].matchingType = {$searchHandler}.MATCHING_TYPE_VIRTUAL_AMAZON;
    {$searchHandler}.searchData[{$id}].matchedAttributes = {$matchedAttributes};
    {$searchHandler}.searchData[{$id}].productAttributes = {$productAttributes};
    {$searchHandler}.searchData[{$id}].destinationAttributes = {$destinationAttributes};
    {$searchHandler}.searchData[{$id}].magentoVariationSet = {$magentoProductVariationsSet};
    {$searchHandler}.searchData[{$id}].amazonVariation = {$amazonVariations};

    ListingGridHandlerObj.productSearchHandler.renderMatchedAttributesVirtualView({$id});
</script>
HTML;

                } else {
                    $value .= <<<HTML
<script type="application/javascript">
    {$searchHandler}.searchData[{$id}] = {};
    {$searchHandler}.searchData[{$id}].matchingType = {$searchHandler}.MATCHING_TYPE_VIRTUAL_MAGENTO;
    {$searchHandler}.searchData[{$id}].matchedAttributes = {$matchedAttributes};
    {$searchHandler}.searchData[{$id}].destinationAttributes = {$destinationAttributes};
    {$searchHandler}.searchData[{$id}].amazonVariation = {$amazonVariations};

    ListingGridHandlerObj.productSearchHandler.renderMatchedAttributesVirtualView({$id});
</script>
HTML;
                }
            }

            return $value . $magentoProductAttributesHtml . $magentoProductAttributesJs;
        }

        $specificsHtml = '';
        $specificsJs = '<script type="text/javascript">';

        // match options for individual
        if ($this->listingProduct->getChildObject()->getVariationManager()->isIndividualType() &&
            $this->listingProduct->getChildObject()->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $channelVariations = array();
            foreach ($variations['asins'] as $asin => $asinAttributes) {
                $channelVariations[$asin] = $asinAttributes['specifics'];
            }

            $this->matcherAttributes->setMagentoProduct($this->listingProduct->getMagentoProduct());
            $this->matcherAttributes->setDestinationAttributes(array_keys($variations['set']));

            if ($this->matcherAttributes->isAmountEqual() && $this->matcherAttributes->isFullyMatched()) {
                $matchedAttributes = $this->matcherAttributes->getMatchedAttributes();

                $this->matcherOptions->setMagentoProduct($this->listingProduct->getMagentoProduct());
                $this->matcherOptions->setDestinationOptions($channelVariations);
                $this->matcherOptions->setMatchedAttributes($matchedAttributes);

                $productOptions = $this->listingProduct->getChildObject()->getVariationManager()
                    ->getTypeModel()->getProductOptions();

                $requestedChildAsin = $this->matcherOptions->getMatchedOptionGeneralId($productOptions);
            }
        }

        if (empty($requestedChildAsin)) {
            $requestedChildAsin = $row->getData('requested_child_id');
        }

        $selectedOptions = array();
        if ($requestedChildAsin) {
            $selectedOptions = $variations['asins'][$requestedChildAsin]['specifics'];
        }

        $specificsHtml .= '<form action="javascript:void(0);">';

        $attributesNames = '<span style="margin-left: 10px;
                                min-width: 100px;
                                max-width: 250px;
                                font-size: 11px;
                                color: #808080;
                                display: inline-block;">';
        $attributeValues = '<span style="margin-left: 5px; display: inline-block;">';
        foreach ($variations['set'] as $specificName => $specific) {
            $attributesNames .= '<span style="margin-bottom: 15px; display: inline-block;">'.
                                    ucfirst(strtolower($specificName)).
                              '</span><br/>';
            $attributeValues .= '<input type="hidden" value="' . $this->getHelper('Data')->escapeHtml($specificName) .
                                '" class="specifics_name_'.$id.'">';
            $attributeValues .=
<<<HTML
<select
    class="select admin__control-select specifics_{$id}"
    onchange="ListingGridHandlerObj.productSearchHandler.specificsChange(this)"
    style="width: 250px; margin-bottom: 5px; font-size: 11px;
        background-position: calc(100% - 12px) -38px, 100%, calc(100% - 3.2rem) 0;"
    id="specific_{$specificName}_{$id}">
HTML;

            $attributeValues .= '<option class="empty" value=""></option>';

            if (!empty($requestedChildAsin)) {
                foreach ($specific as $option) {

                    $selected = '';
                    if ($selectedOptions[$specificName] == $option) {
                        $selected = 'selected';
                    }

                    $option = $this->getHelper('Data')->escapeHtml($option);
                    $attributeValues .= '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
                }
            }

            $attributeValues .= '</select><br/>';

            $specificsJs .= <<<JS
ListingGridHandlerObj.productSearchHandler.specificsChange({id:"specific_{$specificName}_{$id}"});
JS;
        }

        $specificsHtml .= $attributesNames . '</span>';
        $specificsHtml .= $attributeValues . '</span>';
        $specificsHtml .= '</form>';

        $specificsJs .= '</script>';

        $variationAsins = $this->getHelper('Data')->jsonEncode($variations['asins']);
        $variationTree = $this->getHelper('Data')->jsonEncode($this->getChannelVariationsTree($variations));

        $specificsJsonContainer = <<<HTML
<div id="parent_asin_{$id}" style="display: none">{$generalId}</div>
<div id="asins_{$id}" style="display: none;">{$variationAsins}</div>
<div id="channel_variations_tree_{$id}" style="display: none;">{$variationTree}</div>
HTML;

        return $value . $specificsHtml . $specificsJsonContainer . $specificsJs;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if (empty($value) || $row->getData('is_variation_product')) {
            $value = $this->__('N/A');
        } else {
            $value = $this->localeCurrency->getCurrency($this->currency)->toCurrency($value);
        }

        return '<div style="margin-right: 5px;">'.$value.'</div>';
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');
        $iconWarningPath = $this->getSkinUrl('M2ePro/images/warning.png');
        $iconHelpPath = $this->getSkinUrl('M2ePro/images/i_notice.gif');

        if (!$this->listingProduct->getChildObject()->getVariationManager()->isVariationProduct()
            || $this->listingProduct->getChildObject()->getVariationManager()->isIndividualType()) {
            if (!$row->getData('is_variation_product')) {

                return <<<HTML
<a href="javascript:void(0);" onclick="ListingGridHandlerObj.productSearchHandler.mapToGeneralId(
    {$this->productId}, '{$row->getData('general_id')}');">{$assignText}</a>
HTML;
            }

            if (!$row->getData('bad_parent')) {

                $msg = $this->__(
                    'Please select necessary Options for this Amazon Product to be able to assign ASIN/ISBN.'
                );

                return <<<HTML
<span>
    <span id="map_link_{$row->getId()}"><span style="color: #808080">{$assignText}</span></span>&nbsp;
    {$this->getTooltipHtml($msg, 'map_link_error_icon_'.$row->getId())}
</span>
<div id="template_map_link_{$row->getId()}" style="display: none;">
<a href="javascript:void(0);" onclick="ListingGridHandlerObj.productSearchHandler.mapToGeneralId(
    {$this->productId}, '%general_id%', '%options_data%'
);">{$assignText}</a>
</div>
HTML;
            }
        }

        if ($row->getData('is_variation_product') && !$row->getData('bad_parent')) {

            $msg = $this->__(
                'Please map Amazon and Magento Attributes for this Amazon Product to be able to assign ASIN/ISBN.'
            );

            $variations = $row->getData('variations');
            $destinationAttributes = array_keys($variations['set']);

            $this->matcherAttributes->setMagentoProduct($this->listingProduct->getMagentoProduct());
            $this->matcherAttributes->setDestinationAttributes($destinationAttributes);

            if ($this->matcherAttributes->isSourceAmountGreater()) {
                $msg = $this->__(
                    'Please map Magento and Amazon Attributes for this Amazon Product to be able to assign ASIN/ISBN.
                    Be careful, as the number of  Magento Attributes is more than the number of Attributes in Amazon
                    Parent Product. Thus you should select fixed Value for unmatched Magento Variational Attribute.'
                );
            } else if ($this->matcherAttributes->isDestinationAmountGreater()) {
                $msg = $this->__(
                    'Please map Magento and Amazon Attributes for this Amazon Product to be able to assign ASIN/ISBN.
                    Be careful, as the number of Attributes in Amazon Parent Product is more than the number of
                    Magento Attributes. Thus you should select fixed Value for unmatched Amazon Variational Attribute.'
                );
            }

            return <<<HTML
<span>
    <div id="map_link_{$row->getId()}"><span style="color: #808080">{$assignText}</span></div>&nbsp;
    {$this->getTooltipHtml($msg, 'map_link_error_icon_'.$row->getId())}
</span>
<div id="template_map_link_{$row->getId()}" style="display: none;">
<a href="javascript:void(0);" onclick="ListingGridHandlerObj.productSearchHandler.mapToGeneralId(
    {$this->productId}, '{$row->getData('general_id')}', '%options_data%'
);">{$assignText}</a>
</div>
HTML;

        }

        $msg = $this->__(
            'This ASIN/ISBN cannot be assigned to selected Magento Product. <br/>
             This Amazon Product has no Variations. <br/>
             Only Amazon Parent/Child Products can be assigned in "All Variations" Mode.'
        );

        if ($row->getData('is_variation_product') && $row->getData('bad_parent')) {
            $msg =  $this->__(
                'This ASIN/ISBN cannot be assigned to selected Magento Product. <br/>
                 Amazon Service (API) does not return all required information about this Amazon Product.'
            );
        }

        return <<<HTML
<span>
    <span id="map_link_{$row->getId()}"><span style="color: #808080">{$assignText}</span></span>&nbsp;
    {$this->getTooltipHtml($msg, 'map_link_error_icon_'.$row->getId())}
</span>
HTML;
    }

    //########################################

    public function getTooltipHtml($content, $id = '')
    {
        return <<<HTML
<div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content" style="">
        {$content}
    </div>
</div>
HTML;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'help_icon_magento_greater_left' => $this->__('This Amazon Attribute and its Value are virtualized based '.
                'on the selected Magento Variational Attribute and its Value as physically this Amazon Attribute ' .
                'does not exist.'),
            'help_icon_magento_greater_right' => $this->__('Select a particular Option of the Attribute to fix '.
                'it for virtualized Amazon Attribute. Please, be thoughtful as only those Variations of '.
                'Magento Product which contains the selected Option can be sold on Amazon.'),

            'help_icon_amazon_greater_left' => $this->__('This Magento Attribute and its Value are virtualized '.
                'based on the selected Amazon Variational Attribute and its Value as physically this ' .
                'Magento Attribute does not exist.'),
            'help_icon_amazon_greater_right' => $this->__('Select a particular Option of the Attribute to fix ' .
                'it for virtualized Magento Attribute. Please, be thoughtful as your offer will be available only ' .
                'for those Buyers who selected the same Option.'),

            'duplicate_magento_attribute_error' => $this->__('The Magento Attributes which you selected in ' .
                'your settings have the same Labels. Such combination is invalid. Please, add the valid combination ' .
                'of Attributes.'),
            'duplicate_amazon_attribute_error' => $this->__('The Amazon Attributes which you selected in ' .
                'your settings have the same Labels. Such combination is invalid. Please, add the valid combination ' .
                'of Attributes.'),

            'change_option' => $this->__('Change option'),
        ]);

        $searchData = $this->getHelper('Data\GlobalData')->getValue('search_data');

        $searchParamsHtml = <<<HTML
        <input id="amazon_asin_search_type" type="hidden" value="{$searchData['type']}">
        <input id="amazon_asin_search_value" type="hidden" value="{$searchData['value']}">

        <div id="product_search_help_icon_tpl" style="display: none">
            <div class="m2epro-field-tooltip m2epro-field-tooltip-right admin__field-tooltip"
            style="display: inline-block">
                <a class="admin__field-tooltip-action" href="javascript://"></a>
                <div class="admin__field-tooltip-content">
                    <span class="tool-tip-message-text"></span>
                 </div>
            </div>
        </div>
HTML;

        return parent::_toHtml() . $searchParamsHtml;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing/getSuggestedAsinGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    private function getChannelVariationsTree($variations)
    {
        $channelVariations = array();
        foreach ($variations['asins'] as $asin => $asinAttributes) {
            $channelVariations[$asin] = $asinAttributes['specifics'];
        }

        if (empty($channelVariations)) {
            return new stdClass();
        }

        $firstAttribute = key($variations['set']);

        return $this->prepareVariations(
            $firstAttribute, $channelVariations, $variations['set']
        );
    }

    private function prepareVariations($currentAttribute, $variations, $variationsSets,$filters = array())
    {
        $return = false;

        $temp = array_flip(array_keys($variationsSets));

        $lastAttributePosition = count($variationsSets) - 1;
        $currentAttributePosition = $temp[$currentAttribute];

        if ($currentAttributePosition != $lastAttributePosition) {

            $temp = array_keys($variationsSets);
            $nextAttribute = $temp[$currentAttributePosition + 1];

            foreach ($variationsSets[$currentAttribute] as $option) {

                $filters[$currentAttribute] = $option;

                $result = $this->prepareVariations(
                    $nextAttribute,$variations,$variationsSets,$filters
                );

                if (!$result) {
                    continue;
                }

                $return[$currentAttribute][$option] = $result;
            }

            if ($return !== false) {
                ksort($return[$currentAttribute]);
            }

            return $return;
        }

        $return = false;
        foreach ($variations as $key => $magentoVariation) {
            foreach ($magentoVariation as $attribute => $option) {

                if ($attribute == $currentAttribute) {

                    if (count($variationsSets) != 1) {
                        continue;
                    }

                    $values = array_flip($variationsSets[$currentAttribute]);
                    $return = array($currentAttribute => $values);

                    foreach ($return[$currentAttribute] as &$option) {
                        $option = true;
                    }

                    return $return;
                }

                if ($option != $filters[$attribute]) {
                    unset($variations[$key]);
                    continue;
                }

                foreach ($magentoVariation as $tempAttribute => $tempOption) {
                    if ($tempAttribute == $currentAttribute) {
                        $option = $tempOption;
                        $return[$currentAttribute][$option] = true;
                    }
                }
            }
        }

        if (count($variations) < 1) {
            return false;
        }

        if ($return !== false) {
            ksort($return[$currentAttribute]);
        }

        return $return;
    }

    //########################################
}