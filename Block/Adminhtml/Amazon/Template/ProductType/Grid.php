<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param ProductTypeCollectionFactory $productTypeCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        ProductTypeCollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductTypeGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->css->addFile('amazon/product_type.css');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection(): Grid
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Collection $collection */
        $collection = $this->productTypeCollectionFactory->create()
            ->appendTableDictionary()
            ->appendTableMarketplace();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid
     * @throws \Exception
     */
    protected function _prepareColumns(): Grid
    {
        $this->addColumn(
            'title',
            [
                'header' => $this->__('Title'),
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
                'header' => $this->__('Marketplace'),
                'align' => 'left',
                'type' => 'options',
                'width' => '100px',
                'index' => 'marketplace_title',
                'filter_condition_callback' => [$this, 'callbackFilterMarketplace'],
                'options' => $this->getEnabledMarketplaceTitles(),
            ]
        );

        $this->addColumn(
            'create_date',
            [
                'header' => $this->__('Creation Date'),
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
                'header' => $this->__('Update Date'),
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
                'header' => $this->__('Actions'),
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

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Collection $collection
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function callbackFilterMarketplace($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->appendFilterMarketplaceId((int)$value);
    }

    /**
     * @return array
     */
    private function getEnabledMarketplaceTitles(): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $collection */
        $collection = $this->marketplaceCollectionFactory->create()
            ->appendFilterEnabledMarketplaces(\Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->setOrder('title', 'ASC');

        return $collection->toOptionHash();
    }

    /**
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    /**
     * @param $item
     *
     * @return string
     */
    public function getRowUrl($item): string
    {
        return $this->getUrl(
            '*/amazon_template_productType/edit',
            [
                'id' => $item->getData('id'),
                'back' => 1,
            ]
        );
    }

    /**
     * @return array[]
     */
    private function getRowActions(): array
    {
        return [
            [
                'caption' => $this->__('Edit'),
                'url' => [
                    'base' => '*/amazon_template_productType/edit',
                ],
                'field' => 'id',
            ],
            [
                'caption' => $this->__('Delete'),
                'class' => 'action-default scalable add primary',
                'url' => [
                    'base' => '*/amazon_template_productType/delete',
                ],
                'field' => 'id',
                'confirm' => $this->__('Are you sure?'),
            ],
        ];
    }

    /**
     * @param string $value
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $row
     *
     * @return string
     */
    public function callbackColumnTitle($value, $row)
    {
        $value = $this->dataHelper->escapeHtml($value);
        $dictionary = $row->getDictionary();
        $isInvalid = $dictionary->isInvalid();

        if (!$value) {
            $value = $dictionary->getTitle();
        }

        if ($isInvalid) {
            $message = $this->__(
                'This Product Type is no longer supported by Amazon. '
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
