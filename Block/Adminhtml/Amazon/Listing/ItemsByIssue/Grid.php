<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByIssue;

use Ess\M2ePro\Block\Adminhtml\Tag\Switcher as TagSwitcher;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $relationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag */
    private $tagResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory*/
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $relationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Tag $tagResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->tagResource = $tagResource;
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingItemsByIssueGrid');

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('total_items');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/itemsByIssue/grid.css');

        return parent::_prepareLayout();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing/itemsByIssue', ['_current' => true]);
    }

    protected function _prepareCollection()
    {
        $collection = $this->relationCollectionFactory->create();

        $collection->getSelect()->join(
            ['tag' => $this->tagResource->getMainTable()],
            'main_table.tag_id = tag.id'
        );

        $collection->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            'main_table.listing_product_id = lp.id'
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'total_items' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)'),
            'tag_id' => 'tag.id',
            'text' => 'tag.text',
            'error_code' => 'tag.error_code',
        ]);
        $collection->getSelect()->where('lp.component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $collection->getSelect()->where('tag.error_code != ?', \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE);
        $collection->getSelect()->group('main_table.tag_id');

        $accountId = $this->getRequest()->getParam('amazonAccount');
        $accountId = $accountId ? (int)$accountId : null;

        $marketplaceId = $this->getRequest()->getParam('amazonMarketplace');
        $marketplaceId = $marketplaceId ? (int)$marketplaceId : null;

        if ($accountId !== null || $marketplaceId !== null) {
            $collection->join(
                ['l' => $this->listingResource->getMainTable()],
                'lp.listing_id = l.id'
            );
        }

        if ($accountId !== null) {
            $collection->getSelect()->where('l.account_id = ?', $accountId);
        }

        if ($marketplaceId !== null) {
            $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
        }

        $allItemsSubSelect = $this->getAllItemsSubSelect($accountId, $marketplaceId);

        $collection->getSelect()->columns([
            'impact_rate' => new \Magento\Framework\DB\Sql\Expression(
                'COUNT(*) * 100 /(' . $allItemsSubSelect . ')'
            )
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    private function getAllItemsSubSelect(?int $accountId, ?int $marketplaceId): \Magento\Framework\DB\Select
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->getSelect()->where('main_table.component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK);

        if ($accountId !== null || $marketplaceId !== null) {
            $collection->joinInner(
                ['l' => $this->listingResource->getMainTable()],
                'l.id=main_table.listing_id',
                []
            );
        }

        if ($accountId !== null) {
            $collection->getSelect()->where('l.account_id = ?', $accountId);
        }

        if ($marketplaceId !== null) {
            $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
        }

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns('COUNT(*)');

        return $collection->getSelect();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'error_code',
            [
                'header' => __('Error Code'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'error_code',
                'sortable' => false,
                'filter_index' => 'tag.nick',
                'filter_condition_callback' => [$this, 'callbackFilterErrorCode'],
                'column_css_class' => 'listing-by-issue-grid-column-setting',
            ]
        );

        $this->addColumn(
            'issue',
            [
                'header' => __('Issue'),
                'align' => 'left',
                'index' => 'text',
                'type' => 'text',
                'sortable' => false,
                'filter' => false,
            ]
        );

        $this->addColumn(
            'total_items',
            [
                'header' => __('Affected Items'),
                'align' => 'right',
                'type' => 'number',
                'index' => 'total_items',
                'filter' => false,
                'frame_callback' => [$this, 'callbackTotalItems'],
                'column_css_class' => 'listing-by-issue-grid-column-setting',
            ]
        );

        $this->addColumn(
            'impact_rate',
            [
                'header' => __('Impact Rate'),
                'align' => 'right',
                'type' => 'number',
                'index' => 'impact_rate',
                'filter' => false,
                'frame_callback' => [$this, 'callbackImpactRate'],
                'column_css_class' => 'listing-by-issue-grid-column-setting',
            ]
        );

        return parent::_prepareColumns();
    }

    protected function callbackFilterErrorCode(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        if ($errorCode = $column->getFilter()->getValue()) {
            $collection->getSelect()->where('tag.error_code LIKE ?', '%' . $errorCode . '%');
        }
    }

    public function callbackTotalItems(
        string $value,
        \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column,
        bool $isExport
    ): string {
        $url = $this->getUrl(
            '*/amazon_listing/allItems',
            [TagSwitcher::TAG_ID_REQUEST_PARAM_KEY => $row->getData('tag_id')]
        );

        return sprintf("<a href='%s'>%s</a>", $url, $row->getData('total_items'));
    }

    public function callbackImpactRate(
        string $value,
        \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column,
        bool $isExport
    ): string {
        return round((float)$value, 1) . '%';
    }

    /**
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Relation $item
     *
     * @return false
     */
    public function getRowUrl($item): bool
    {
        return false;
    }
}
