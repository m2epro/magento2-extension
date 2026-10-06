<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\AdvancedFilter;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AdvancedFilter\AbstractGrid
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $autoFilterCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $templateProductTypeResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $autoFilterCollectionFactory,
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $templateProductTypeResource,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($listing, $context, $backendHelper, $data);
        $this->autoFilterCollectionFactory = $autoFilterCollectionFactory;
        $this->templateProductTypeResource = $templateProductTypeResource;
    }

    protected function _prepareCollection(): self
    {
        $collection = $this->autoFilterCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_LISTING_ID,
            ['eq' => (int)$this->getListing()->getId()]
        );

        $collection->joinLeft(
            ['pt' => $this->templateProductTypeResource->getMainTable()],
            sprintf(
                'pt.%s = second_table.%s',
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced\Filter::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID,
            ),
            [
                'product_type_title' => \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::COLUMN_TITLE,
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): self
    {
        $this->addColumn('title', [
            'header' => __('Title'),
            'align' => 'left',
            'type' => 'text',
            'escape' => true,
            'index' => 'title',
            'filter_index' => 'title',
        ]);

        $this->addColumn('filters', [
            'header' => __('Advanced Filter'),
            'align' => 'left',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'frame_callback' => [$this, 'callbackColumnFilters'],
        ]);

        $this->addColumn('product_type', [
            'header' => __('Product Type'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'product_type_title',
            'filter_index' => sprintf(
                'pt.%s',
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::COLUMN_TITLE
            ),
            'frame_callback' => [$this, 'callbackColumnProductType'],
        ]);

        $this->addColumn('action', [
            'header' => __('Actions'),
            'align' => 'left',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'actions' => [
                0 => [
                    'label' => __('Edit Rule'),
                    'value' => 'advancedFilterStepOne',
                ],
                1 => [
                    'label' => __('Delete Rule'),
                    'value' => 'advancedFilterDelete',
                ],
            ],
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);

        return parent::_prepareColumns();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/amazon_listing_autoAction/getAdvancedFilterGrid', ['_current' => true]);
    }

    /**
     * @param mixed $value
     * @param \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     */
    public function callbackColumnProductType($value, $row, $column, $isExport): string
    {
        if (empty($value)) {
            return (string)__('Not Set');
        }

        return (string)$value;
    }
}
