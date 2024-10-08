<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $dictionaryProductTypeResource;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $dictionaryProductTypeResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->dictionaryProductTypeResource = $dictionaryProductTypeResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceRepository = $marketplaceRepository;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartProductTypeGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection(): Grid
    {
        $collection = $this->productTypeCollectionFactory->create();

        $collection->getSelect()->join(
            ['adpt' => $this->dictionaryProductTypeResource->getMainTable()],
            'adpt.id = main_table.dictionary_product_type_id',
            ['product_type_title' => 'adpt.title']
        );

        $collection->getSelect()->join(
            ['m' => $this->marketplaceResource->getMainTable()],
            'm.id = adpt.marketplace_id AND m.status = 1',
            ['marketplace_title' => 'm.title']
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): Grid
    {
        $this->addColumn(
            'title',
            [
                'header' => (string)__('Title'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'title',
                'escape' => true,
                'filter_index' => 'main_table.title',
                'frame_callback' => [$this, 'callbackColumnTitle'],
            ]
        );

        $this->addColumn(
            'marketplace',
            [
                'header' => (string)__('Marketplace'),
                'align' => 'left',
                'type' => 'options',
                'width' => '100px',
                'index' => 'marketplace_title',
                'filter_condition_callback' => [$this, 'callbackFilterMarketplace'],
                'options' => $this->getEnabledMarketplaceOptions(),
            ]
        );

        $this->addColumn(
            'create_date',
            [
                'header' => (string)__('Creation Date'),
                'align' => 'left',
                'width' => '150px',
                'type' => 'datetime',
                'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'filter_time' => true,
                'format' => \IntlDateFormatter::MEDIUM,
                'index' => 'create_date',
                'filter_index' => 'main_table.create_date',
            ]
        );

        $this->addColumn(
            'update_date',
            [
                'header' => (string)__('Update Date'),
                'align' => 'left',
                'width' => '150px',
                'type' => 'datetime',
                'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'filter_time' => true,
                'format' => \IntlDateFormatter::MEDIUM,
                'index' => 'update_date',
                'filter_index' => 'main_table.update_date',
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header' => (string)__('Actions'),
                'align' => 'left',
                'width' => '100px',
                'type' => 'action',
                'index' => 'actions',
                'filter' => false,
                'sortable' => false,
                'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
                'getter' => 'getId',
                'actions' => $this->getRowActions(),
            ]
        );

        return parent::_prepareColumns();
    }

    protected function callbackFilterMarketplace($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->getSelect()->where('adpt.marketplace_id = ?', $value);
    }

    private function getEnabledMarketplaceOptions(): array
    {
        $options = [];
        foreach ($this->marketplaceRepository->findActive() as $marketplace) {
            $options[$marketplace->getId()] = $marketplace->getTitle();
        }

        return $options;
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($item): string
    {
        return $this->getUrl(
            '*/walmart_productType/edit',
            [
                'id' => $item->getData('id'),
                'back' => 1,
            ]
        );
    }

    private function getRowActions(): array
    {
        return [
            [
                'caption' => (string)__('Edit'),
                'url' => [
                    'base' => '*/walmart_productType/edit',
                ],
                'field' => 'id',
            ],
            [
                'caption' => (string)__('Delete'),
                'class' => 'action-default scalable add primary',
                'url' => [
                    'base' => '*/walmart_productType/delete',
                ],
                'field' => 'id',
                'confirm' => (string)__('Are you sure?'),
            ],
        ];
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\ProductType $row
     */
    public function callbackColumnTitle($value, $row)
    {
        $dictionary = $row->getDictionary();
        $isInvalid = $dictionary->isInvalid();

        if (empty($value)) {
            $value = $dictionary->getTitle();
        }

        if ($isInvalid) {
            $message = (string)__(
                'This Product Type is no longer supported by Walmart. '
                . 'Please assign another Product Type to the products that use it.'
            );

            $value = <<<HTML
<span class="product-type-dictionary-warning">
    $value
    {$this->getTooltipHtml($message, true)}
</span>
HTML;
        }

        return $value;
    }
}
