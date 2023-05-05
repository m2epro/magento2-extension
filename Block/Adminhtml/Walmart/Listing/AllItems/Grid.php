<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems;

use Ess\M2ePro\Model\Listing\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var array */
    private $parentAndChildReviseScheduledCache = [];
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    private $magentoProductCollectionFactory;
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    private $localeCurrency;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingSearchProductGrid');

        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->distinct();
        $collection->setListingProductModeOn();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            [
                'lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable(),
            ],
            'product_id=entity_id',
            [
                'id' => 'id',
                'status' => 'status',
                'component_mode' => 'component_mode',
                'listing_id' => 'listing_id',
                'additional_data' => 'additional_data',
            ]
        );
        $collection->joinTable(
            [
                'wlp' => $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable(
                ),
            ],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',
                'listing_other_id' => new \Zend_Db_Expr('NULL'),
                'variation_parent_id' => 'variation_parent_id',
                'is_variation_parent' => 'is_variation_parent',
                'variation_child_statuses' => 'variation_child_statuses',
                'online_sku' => 'sku',
                'gtin' => 'gtin',
                'upc' => 'upc',
                'ean' => 'ean',
                'isbn' => 'isbn',
                'wpid' => 'wpid',
                'item_id' => 'item_id',
                'online_qty' => 'online_qty',
                'online_price' => 'online_price',
                'is_online_price_invalid' => 'is_online_price_invalid',
            ],
            'variation_parent_id IS NULL'
        );
        $collection->joinTable(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'id=listing_id',
            [
                'store_id' => 'store_id',
                'account_id' => 'account_id',
                'marketplace_id' => 'marketplace_id',
                'listing_title' => 'title',
            ]
        );

        $accountId = (int)$this->getRequest()->getParam('walmartAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('walmartMarketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('l.account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $collection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
        $collection->getSelect()->join(
            [
                'lps' => $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
                                                   ->getResource()->getMainTable(),
            ],
            'lps.listing_product_id=main_table.id',
            []
        );

        $collection->addFieldToFilter('is_variation_parent', 0);
        $collection->addFieldToFilter('variation_parent_id', ['in' => $this->getCollection()->getColumnValues('id')]);
        $collection->addFieldToFilter('lps.action_type', \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'variation_parent_id' => 'second_table.variation_parent_id',
                'count' => new \Zend_Db_Expr('COUNT(lps.id)'),
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        foreach ($collection->getItems() as $item) {
            $this->parentAndChildReviseScheduledCache[$item->getData('variation_parent_id')] = true;
        }

        return parent::_afterLoadCollection();
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');
        $title = $this->dataHelper->escapeHtml($title);

        $listingWord = __('Listing');
        $listingTitle = $this->dataHelper->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $listingUrl = $this->getUrl(
            '*/walmart_listing/view',
            ['id' => $row->getData('listing_id')]
        );

        $value = <<<HTML
<div style="margin-bottom: 5px">{$title}</div>
<strong>{$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->walmartFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->walmartFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $value .= '<br/><strong>' . __('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            . '<strong>' . __('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $this->dataHelper->escapeHtml($row->getData('sku'));
        $skuWord = __('SKU');

        $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
{$sku}
HTML;

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isVariationParent()) {
            $productAttributes = $variationManager->getTypeModel()->getProductAttributes();

            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $attributesStr = '';

            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {
                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';
                    } elseif (in_array($attribute, array_keys($virtualChannelAttributes))) {
                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';
                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }

            $value .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey; margin-top: 15px">
    {$attributesStr}
</div>
HTML;
        }

        if (
            $variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        ) {
            $optionsStr = '';
            $productOptions = $variationManager->getTypeModel()->getProductOptions();

            foreach ($productOptions as $attribute => $option) {
                $attribute = $this->dataHelper->escapeHtml($attribute);
                if ($option === '' || $option === null) {
                    $option = '--';
                }
                $option = $this->dataHelper->escapeHtml($option);

                $optionsStr .= <<<HTML
<strong>{$attribute}</strong>:&nbsp;{$option}<br/>
HTML;
            }

            $value .= <<<HTML
<br/>
<div style="font-size: 11px; color: grey;">
    {$optionsStr}
</div>
<br/>
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $value = $this->getProductStatus($row->getData('status'));

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();

            return $value . $this->getStatusChangeReasons($statusChangeReasons)
                . $this->getScheduledTag($row)
                . $this->getLockedTag($row);
        }

        $html = '';

        $sNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        $sListed = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        $sStopped = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        $sBlocked = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

        $variationsStatuses = $row->getData('variation_child_statuses');

        if (empty($variationsStatuses)) {
            return $this->getProductStatus($row->getData('status'))
                . $this->getScheduledTag($row)
                . $this->getLockedTag($row);
        }

        $sortedStatuses = [];
        $variationsStatuses = \Ess\M2ePro\Helper\Json::decode($variationsStatuses);

        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed]) && $sortedStatuses[$sListed] = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sStopped]) && $sortedStatuses[$sStopped] = $variationsStatuses[$sStopped];
        isset($variationsStatuses[$sBlocked]) && $sortedStatuses[$sBlocked] = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {
            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '[' . $productsCount . ']';
            $html .= $this->getProductStatus($status) . '&nbsp;' . $productsCount . '<br/>';
        }

        return $html . $this->getScheduledTag($row)
            . $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('entity_id');

        $urlData = [
            'id' => $row->getData('listing_id'),
            'filter' => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}"),
        ];

        $searchedChildHtml = '';
        if ($this->wasFoundByChild($row)) {
            $urlData['child_listing_product_ids'] = $this->getChildListingProductIds($row);

            $searchedChildHtml = <<<HTML
<br/>
<div class="fix-magento-tooltip searched_child_product" style="margin-top: 4px; padding-left: 10px;">
    {$this->getTooltipHtml(
                __(
                    'A Product you are searching for is found as part of a Multi-Variational Product.' .
                    ' Click on the arrow icon to manage it individually.'
                )
            )}
</div>
HTML;
        }

        $manageUrl = $this->getUrl('*/walmart_listing/view/', $urlData);
        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$this->__('Go to Listing')}" target="_blank" href="{$manageUrl}">
    <img src="{$this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png')}" alt="{$this->__('Go to Listing')}" /></a>
</div>
HTML;

        return $searchedChildHtml . $html;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') && $row->getData('status') == Product::STATUS_NOT_LISTED)) {
            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $currentOnlinePrice = (float)$row->getData('online_price');

        if (empty($currentOnlinePrice) || $row->getData('status') == Product::STATUS_BLOCKED) {
            return __('N/A');
        }

        $marketplaceId = $row->getData('marketplace_id');
        $currency = $this->walmartFactory->getObjectLoaded('Marketplace', $marketplaceId)
                                         ->getChildObject()
                                         ->getDefaultCurrency();
        $priceValue = $this->convertAndFormatPriceCurrency($currentOnlinePrice, $currency);

        if ($row->getData('is_online_price_invalid')) {
            $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
            $msg = '<p>' . __($message) . '</p>';
            if (empty($msg)) {
                return $priceValue;
            }

            $priceValue .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($message, 'map_link_defected_message_icon_' . $row->getId())}
</span>
HTML;

            return $priceValue;
        }

        if ($row->getData('is_variation_parent')) {
            $noticeText = __('The value is calculated as minimum price of all Child Products.');
            $priceHtml = <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip" style="display: inline;">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$noticeText}
    </div>
</div>
HTML;

            $priceHtml .= "<span>{$priceValue}</span><br />";

            return $priceHtml;
        }

        if ($currentOnlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        }

        return $priceValue;
    }

    private function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $tempLocks = $listingProduct->getProcessingLocks();

        $html = '';
        $childCount = 0;

        foreach ($tempLocks as $lock) {
            switch ($lock->getTag()) {
                case 'list_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('List in Progress')
                        . '...]</span>';
                    break;

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Relist in Progress')
                        . '...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Revise in Progress')
                        . '...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Stop in Progress')
                        . '...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Stop And Remove in Progress')
                        . '...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Remove in Progress')
                        . '...]</span>';
                    break;

                case 'child_products_in_action':
                    $childCount++;
                    break;

                default:
                    break;
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[' . __('Child(s) in Action') . '...]</span>';
        }

        return $html;
    }

    private function getScheduledTag($row)
    {
        $html = '';

        $scheduledAction = $this->getListingProductScheduledAction($row['id']);
        if (!$scheduledAction) {
            return $html;
        }

        switch ($scheduledAction->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $html .= $this->getScheduledTagHtml('List');
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $html .= $this->getScheduledTagHtml('Relist');
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $reviseParts = $this->getScheduledReviseParts($scheduledAction, $row->getData('id'));
                if (!empty($reviseParts)) {
                    $html .= $this->getScheduledTagHtml('Revise of ' . implode(', ', $reviseParts));
                } else {
                    $html .= $this->getScheduledTagHtml('Revise');
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $html .= $this->getScheduledTagHtml('Stop');
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $html .= $this->getScheduledTagHtml('Retire');
                break;

            default:
                break;
        }

        return $html;
    }

    private function getListingProductScheduledAction($listingProductId)
    {
        $scheduledActionsCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')
            ->getCollection()
            ->addFieldToFilter('listing_product_id', $listingProductId);

        return $scheduledActionsCollection->getFirstItem()->getId()
            ? $scheduledActionsCollection->getFirstItem()
            : null;
    }

    private function getScheduledReviseParts($scheduledAction, $listingProductId)
    {
        if (
            !empty($scheduledAction->getAdditionalData()['configurator']) &&
            !isset($this->parentAndChildReviseScheduledCache[$listingProductId])
        ) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            $configurator->setUnserializedData($scheduledAction->getAdditionalData()['configurator']);

            if ($configurator->isIncludingMode()) {
                $reviseParts = [];

                if ($configurator->isQtyAllowed()) {
                    $reviseParts[] = 'QTY';
                }

                if ($configurator->isPriceAllowed()) {
                    $reviseParts[] = 'Price';
                }

                if ($configurator->isPromotionsAllowed()) {
                    $reviseParts[] = 'Promotions';
                }

                if ($configurator->isDetailsAllowed()) {
                    $params = $scheduledAction->getAdditionalData()['params'];

                    if (isset($params['changed_sku'])) {
                        $reviseParts[] = 'SKU';
                    }

                    if (isset($params['changed_identifier'])) {
                        $reviseParts[] = strtoupper($params['changed_identifier']['type']);
                    }

                    $reviseParts[] = 'Details';
                }

                return $reviseParts;
            }
        }

        return [];
    }

    private function getScheduledTagHtml($text)
    {
        return '<br/><span style="color: #605fff">[' . $text . ' is Scheduled...]</span>';
    }

    protected function callbackFilterProductId($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->addFieldToFilter('product_id', $cond);

        $collection->getSelect()->joinLeft(
            ['product_id_subQuery' => $childCollection->getSelect()],
            'product_id_subQuery.variation_parent_id=lp.id',
            [
                'product_id_child_listing_product_ids' => 'child_listing_product_ids',
                'product_id_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', $cond],
            ['attribute' => 'product_id_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->getSelect()->joinLeft(
            [
                'cpe' => $this->databaseHelper
                    ->getTableNameWithPrefix('catalog_product_entity'),
            ],
            'cpe.entity_id=main_table.product_id',
            []
        );
        $childCollection->addFieldToFilter('cpe.sku', ['like' => '%' . $value . '%']);

        $collection->getSelect()->joinLeft(
            ['product_sku_subQuery' => $childCollection->getSelect()],
            'product_sku_subQuery.variation_parent_id=lp.id',
            [
                'product_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'product_sku_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
                ['attribute' => 'listing_title', 'like' => '%' . $value . '%'],
                ['attribute' => 'product_sku_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
            ]
        );
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $childCollection = $this->getChildProductsCollection();
        $childCollection->addFieldToFilter('sku', ['like' => '%' . $value . '%']);

        $collection->getSelect()->joinLeft(
            ['online_sku_subQuery' => $childCollection->getSelect()],
            'online_sku_subQuery.variation_parent_id=lp.id',
            [
                'online_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'online_sku_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'online_sku', 'like' => '%' . $value . '%'],
            ['attribute' => 'online_sku_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
main_table.gtin LIKE '%{$value}%' OR
main_table.upc LIKE '%{$value}%' OR
main_table.ean LIKE '%{$value}%' OR
main_table.isbn LIKE '%{$value}%' OR
main_table.wpid LIKE '%{$value}%' OR
main_table.item_id LIKE '%{$value}%'
SQL;

        $childCollection = $this->getChildProductsCollection();
        $childCollection->getSelect()->where($where);

        $collection->getSelect()->joinLeft(
            ['gtin_subQuery' => $childCollection->getSelect()],
            'gtin_subQuery.variation_parent_id=lp.id',
            [
                'gtin_child_listing_product_ids' => 'child_listing_product_ids',
                'gtin_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter(
            [
                ['attribute' => 'gtin', 'like' => '%' . $value . '%'],
                ['attribute' => 'upc', 'like' => '%' . $value . '%'],
                ['attribute' => 'ean', 'like' => '%' . $value . '%'],
                ['attribute' => 'isbn', 'like' => '%' . $value . '%'],
                ['attribute' => 'wpid', 'like' => '%' . $value . '%'],
                ['attribute' => 'item_id', 'like' => '%' . $value . '%'],
                ['attribute' => 'gtin_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
            ]
        );
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = 'lp.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $where .= ' AND online_qty >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= ' AND online_qty <= ' . $quoted;
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = 'lp.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $condition .= ' AND wlp.online_price >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            $quoted = $collection->getConnection()->quote($value['to']);
            $condition .= ' AND wlp.online_price <= ' . $quoted;
        }

        $collection->getSelect()->where($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "status = {$value} OR (variation_child_statuses REGEXP '\"{$value}\":[^0]') AND is_variation_parent = 1"
        );
    }

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    protected function getMagentoChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection()
                                                ->addFieldToSelect('listing_product_variation_id')
                                                ->addFieldToFilter(
                                                    'main_table.component_mode',
                                                    \Ess\M2ePro\Helper\Component\Walmart::NICK
                                                );

        $collection->getSelect()->joinLeft(
            [
                'lpv' => $this->activeRecordFactory->getObject('Listing_Product_Variation')
                                                   ->getResource()->getMainTable(),
            ],
            'lpv.id=main_table.listing_product_variation_id',
            ['listing_product_id']
        );
        $collection->getSelect()->joinLeft(
            ['alp' => $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable()],
            'alp.listing_product_id=lpv.listing_product_id',
            ['variation_parent_id']
        );
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT alp.listing_product_id)'),
                'variation_parent_id' => 'alp.variation_parent_id',
                'searched_by_child' => new \Zend_Db_Expr('1'),
            ]
        );

        $collection->getSelect()->group('alp.variation_parent_id');

        return $collection;
    }

    protected function getChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getCollection()
                                                ->addFieldToFilter('variation_parent_id', ['notnull' => true])
                                                ->addFieldToFilter('is_variation_product', 1);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(listing_product_id)'),
                'variation_parent_id' => 'variation_parent_id',
                'searched_by_child' => new \Zend_Db_Expr('1'),
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        return $collection;
    }

    protected function wasFoundByChild($row)
    {
        foreach (['product_id', 'product_sku', 'online_sku', 'gtin'] as $item) {
            $searchedByChild = $row->getData("{$item}_searched_by_child");
            if (!empty($searchedByChild)) {
                return true;
            }
        }

        return false;
    }

    protected function getChildListingProductIds($row)
    {
        $ids = [];

        foreach (['product_id', 'product_sku', 'online_sku', 'gtin'] as $item) {
            $itemIds = $row->getData("{$item}_child_listing_product_ids");
            if (empty($itemIds)) {
                continue;
            }

            foreach (explode(',', $itemIds) as $itemId) {
                !isset($ids[$itemId]) && $ids[$itemId] = 0;
                $ids[$itemId]++;
            }
        }

        $maxCount = max($ids);
        foreach ($ids as $id => $count) {
            if ($count < $maxCount) {
                unset($ids[$id]);
            }
        }

        return implode(',', array_keys($ids));
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
            'filter_condition_callback' => [$this, 'callbackFilterProductId'],
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Listing / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('online_sku', [
            'header' => __('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'online_sku',
            'filter_index' => 'online_sku',
            'show_edit_sku' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku::class,
            'filter_condition_callback' => [$this, 'callbackFilterOnlineSku'],
        ]);

        $this->addColumn('gtin', [
            'header' => __('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'show_edit_identifier' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin::class,
            'filter_index' => 'gtin',
            'filter_condition_callback' => [$this, 'callbackFilterGtin'],
        ]);

        $this->addColumn('online_qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Qty::class,
            'filter_condition_callback' => [$this, 'callbackFilterQty'],
        ]);

        $this->addColumn('online_price', [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ]);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '125px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => __('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => __('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => __('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => __('Incomplete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        $listingType = $this->getRequest()->getParam(
            'listing_type',
            \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            unset($statusColumn['options'][\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);
        }

        $this->addColumn('status', $statusColumn);

        $this->addColumn('goto_listing_item', [
            'header' => __('Manage'),
            'align' => 'center',
            'width' => '50px',
            'type' => 'text',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);

        return parent::_prepareColumns();
    }

    protected function getProductStatus($status)
    {
        switch ($status) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                return '<span style="color: gray;">' . __('Not Listed') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                return '<span style="color: green;">' . __('Active') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                return '<span style="color: red;">' . __('Inactive') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                return '<span style="color: orange; font-weight: bold;">' .
                    __('Incomplete') . '</span>';
        }

        return '';
    }

    protected function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
            . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
            . '</li>';

        return <<<HTML
        <div style="display: inline-block; width: 16px; margin-left: 3px; margin-right: 4px;">
            {$this->getTooltipHtml($html)}
        </div>
HTML;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing/allItems', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }
}
