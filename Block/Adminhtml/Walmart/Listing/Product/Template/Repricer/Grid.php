<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Repricer;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private int $accountId;
    private array $productsIds;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer\CollectionFactory $templateRepricerCollectionFactory;

    public function __construct(
        int $accountId,
        array $productsIds,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer\CollectionFactory $templateRepricerCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->accountId = $accountId;
        $this->productsIds = $productsIds;
        $this->templateRepricerCollectionFactory = $templateRepricerCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartTemplateRepricerGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(true);
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $this->setEmptyText(
            sprintf(
                '<p>%s <a href="javascript:void(0);" class="new-repricer-template">%s</a></p>',
                __('Repricer Policies are not found.'),
                __('Create New Repricer Policy.')
            )
        );

        $collection = $this->templateRepricerCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_ACCOUNT_ID,
            $this->accountId
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function _prepareColumns(): void
    {
        $this->addColumn('title', [
            'header' => __('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'title',
            'escape' => false,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnTitle'],
        ]);

        $this->addColumn('action', [
            'header' => __('Action'),
            'align' => 'left',
            'type' => 'number',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnAction'],
        ]);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout(): self
    {
        $this->setChild(
            'refresh_button',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                 ->setData([
                     'id' => 'repricer_template_refresh_btn',
                     'label' => __('Refresh'),
                     'class' => 'action primary',
                     'onclick' => "ListingGridObj.repricerHandler.loadRepricerPolicyGrid()",
                 ])
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getRefreshButtonHtml(): string
    {
        return $this->getChildHtml('refresh_button');
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml(): string
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    public function callbackColumnTitle($value, $row, $column, $isExport): string
    {
        $templateEditUrl = $this->getUrl('*/walmart_template_repricer/edit', [
            'id' => $row->getData('id'),
            'close_on_save' => true,
        ]);

        return sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $templateEditUrl,
            $this->_escaper->escapeHtml($value)
        );
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        return sprintf(
            '<a href="javascript:void(0)" class="assign-repricer-template" data-template-id="%s">%s</a>',
            $value,
            __('Assign')
        );
    }

    /**
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'account_id' => $this->accountId,
            ],
            'products_ids' => implode(',', $this->productsIds),
        ]);
    }

    /**
     * @param $item
     *
     * @return bool
     */
    public function getRowUrl($item): bool
    {
        return false;
    }
}
