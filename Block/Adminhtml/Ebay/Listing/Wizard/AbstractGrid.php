<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Block\Adminhtml\Listing\Product\ShowOthersListingsProductsFilter;
use Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid as EbayMagentoGrid;
use Ess\M2ePro\Helper\Magento\Product as ProductHelper;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product as ProductModel;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;
use Ess\M2ePro\Helper\Module;
use Magento\Catalog\Model\Product\Type;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation;

abstract class AbstractGrid extends EbayMagentoGrid
{
    protected ProductHelper $magentoProductHelper;
    protected CollectionFactory $magentoProductCollectionFactory;
    protected Type $type;
    private ListingResource $listingResource;
    private ProductModel $listingProductResource;
    private ListingRuntimeStorage $uiListingRuntimeStorage;
    private WizardRuntimeStorage $uiWizardRuntimeStorage;
    private Module $moduleHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation */
    private ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation;

    public function __construct(
        ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        ListingResource $listingResource,
        ProductModel $listingProductResource,
        CollectionFactory $magentoProductCollectionFactory,
        Module $moduleHelper,
        Type $type,
        ProductHelper $magentoProductHelper,
        Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->type = $type;
        $this->magentoProductHelper = $magentoProductHelper;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        $this->moduleHelper = $moduleHelper;
        $this->excludeSimpleProductsInVariation = $excludeSimpleProductsInVariation;

        parent::__construct($context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingWizardProductGrid' . $this->getListing()->getId());
        // ---------------------------------------

        $this->hideMassactionDropDown = true;
        $this->showAdvancedFilterProductsOption = false;
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('type_id');

        $collection->setStoreId($this->getListing()->getStoreId());
        $collection->addStoreFilter();
        $collection->joinStockItem();

        // ---------------------------------------
        $collection->getSelect()->distinct();
        // ---------------------------------------

        // Set filter store
        // ---------------------------------------
        $store = $this->_getStore();

        if ($store->getId()) {
            $collection->joinAttribute(
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId(),
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId(),
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId(),
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                $store->getId(),
            );
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        $hideSimpleProducts = true;
        if ($this->getRequest()->has(ShowOthersListingsProductsFilter::PARAM_NAME_SHOW_CHILD_PRODUCTS_IN_VARIATIONS)) {
            $hideSimpleProducts = false;
        }

        $includeSimpleProductsInVariation = $this
            ->getRequest()
            ->has(ShowOthersListingsProductsFilter::PARAM_NAME_SHOW_CHILD_PRODUCTS_IN_VARIATIONS);
        if (!$includeSimpleProductsInVariation) {
            $this->excludeSimpleProductsInVariation->filter($collection, (int)$this->getListing()->getId());
        }

        // Hide products others listings
        // ---------------------------------------
        $hideParam = true;
        if ($this->getRequest()->has(ShowOthersListingsProductsFilter::PARAM_NAME_SHOW_PRODUCT_IN_OTHER_LISTING)) {
            $hideParam = false;
        }

        if ($hideParam || $this->getListing()->getId() !== null) {
            $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
            $dbExcludeSelect = $collection->getConnection()
                                          ->select()
                                          ->from($lpTable, new \Zend_Db_Expr('DISTINCT `product_id`'));

            if ($hideParam) {
                $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
                $dbExcludeSelect->join(
                    ['l' => $lTable],
                    '`l`.`id` = `listing_id`',
                    null
                );

                $dbExcludeSelect->where('`l`.`account_id` = ?', $this->getListing()->getAccountId());
                $dbExcludeSelect->where('`l`.`marketplace_id` = ?', $this->getListing()->getMarketplaceId());
                $dbExcludeSelect->where('`l`.`component_mode` = ?', \Ess\M2ePro\Helper\Component\Ebay::NICK);
            } else {
                $dbExcludeSelect->where('`listing_id` = ?', (int)$this->getListing()->getId());
            }

            $collection->getSelect()
                       ->joinLeft(['sq' => $dbExcludeSelect], 'sq.product_id = e.entity_id', [])
                       ->where('sq.product_id IS NULL');
        }
        // ---------------------------------------

        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->getListing()->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
        ]);

        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'type_id',
            'filter_index' => 'type_id',
            'options' => $this->getProductTypes(),
        ]);

        $this->addColumn('is_in_stock', [
            'header' => __('Stock Availability'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'options' => [
                '1' => __('In Stock'),
                '0' => __('Out of Stock'),
            ],
            'frame_callback' => [$this, 'callbackColumnIsInStock'],
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku',
        ]);

        $store = $this->_getStore();

        $this->addColumn('price', [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'price',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price::class,
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'price',
            'filter_index' => 'price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
        ]);

        $this->addColumn('qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'qty',
            'filter_index' => 'qty',
            'frame_callback' => [$this, 'callbackColumnQty'],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        return parent::_prepareMassaction();
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() === 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left',
                );
            }
        }

        return parent::_addColumnFilterToCollection($column);
    }

    protected function _getStore(): \Magento\Store\Model\Store
    {
        return $this->_storeManager->getStore($this->getListing()->getStoreId());
    }

    abstract protected function getSelectedProductsCallback();

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
                require([
                    'M2ePro/General/PhpFunctions',
                ], function(){

                    wait(function() {
                        return typeof ProductGridObj != 'undefined';
                    }, function() {
                      return ProductGridObj.massactionMassSelectStyleFix();
                    }, 20);
                });
    JS,
            );

            return parent::_toHtml();
        }

        // ---------------------------------------

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_product/add',
                ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
            ),
            'ebay_listing_wizard_product_add',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_product/completeStep',
                ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
            ),
            'ebay_listing_wizard_product_complete_with_id',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_product_add/setAutoActionPopupShown',
            ),
            'ebay_listing_product_add/setAutoActionPopupShown',
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Ebay_Listing_AutoAction',
                ['listing_id' => $this->getListing()->getId()]
            )
        );

        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->addTranslations([
            'Category Settings' => __('Category Settings'),
            'Specifics' => __('Specifics'),
            'Based on Magento Categories' => __('Based on Magento Categories'),
            'You must select at least 1 Category.' =>
                __('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' =>
                __('Rule with the same Title already exists.'),
            'Listing Settings Customization' => __('Listing Settings Customization'),
            'Auto Add/Remove Rules' => __('Auto Add/Remove Rules'),
        ]);

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        // ---------------------------------------

        $showAutoActionPopup = \Ess\M2ePro\Helper\Json::encode(
            !$this->moduleHelper->getRegistry()->getValue('/ebay/listing/autoaction_popup/is_shown/')
        );

        $this->js->add(
            <<<JS
        require([
            'M2ePro/Ebay/Listing/Wizard/Product/Add',
            'M2ePro/Plugin/AreaWrapper',
            'M2ePro/Plugin/ProgressBar',
            'M2ePro/Ebay/Listing/AutoAction'
        ], function(){

            window.WrapperObj = new AreaWrapper('add_products_container');
            window.ProgressBarObj = new ProgressBar('add_products_progress_bar');

            window.ListingProductAddObj = new ListingWizardProductAdd({
                show_autoaction_popup: {$showAutoActionPopup},

                get_selected_products: {$this->getSelectedProductsCallback()}
            });

            window.ListingAutoActionObj = new EbayListingAutoAction();

            wait(function() {
                return typeof ProductGridObj != 'undefined';
            }, function() {
              return ProductGridObj.massactionMassSelectStyleFix();
            }, 20);
        });
    JS,
        );

        return parent::_toHtml();
    }

    private function getProductTypes(): array
    {
        $magentoProductTypes = $this->type->getOptionArray();
        $knownTypes = $this->magentoProductHelper->getOriginKnownTypes();

        foreach ($magentoProductTypes as $type => $magentoProductTypeLabel) {
            if (in_array($type, $knownTypes)) {
                continue;
            }

            unset($magentoProductTypes[$type]);
        }

        return $magentoProductTypes;
    }

    private function getListing(): Listing
    {
        return $this->uiListingRuntimeStorage->getListing();
    }
}
