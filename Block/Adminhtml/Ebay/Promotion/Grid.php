<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Promotion;

use Ess\M2ePro\Model\Ebay\Promotion;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private int $accountId;
    private int $marketplaceId;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\Ebay\Promotion\DashboardUrlGenerator $dashboardUrlGenerator;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Promotion\DashboardUrlGenerator $dashboardUrlGenerator,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->accountId = $data['accountId'];
        $this->marketplaceId = $data['marketplaceId'];
        $this->collectionFactory = $collectionFactory;
        $this->dashboardUrlGenerator = $dashboardUrlGenerator;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct(): void
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('promotionGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('promotion_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setDefaultLimit(20);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection(): Grid
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter('main_table.marketplace_id', $this->marketplaceId);
        $collection->addFieldToFilter('main_table.account_id', $this->accountId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns(): void
    {
        $this->addColumn('name', [
            'header' => __('Name'),
            'align' => 'left',
            'type' => 'text',
            'width' => '150px',
            'index' => 'name',
            'sortable' => true,
        ]);

        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'right',
            'type' => 'options',
            'width' => '75px',
            'index' => 'type',
            'filter_index' => 'type',
            'sortable' => true,
            'options' => $this->getFormattedPromotionTypeOptions(),
            'filter_condition_callback' => [$this, 'callbackFilterType'],
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'align' => 'right',
            'type' => 'options',
            'width' => '75px',
            'index' => 'status',
            'sortable' => true,
            'options' => $this->getFormattedPromotionStatusOptions(),
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ]);

        $this->addColumn('start_date', [
            'header' => __('Start Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => 'start_date',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'sortable' => true,
            'filter_index' => 'start_date',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\DateTime::class,
        ]);

        $this->addColumn('end_date', [
            'header' => __('End Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => 'end_date',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'sortable' => true,
            'filter_index' => 'end_date',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\DateTime::class,
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'left',
            'type' => 'text',
            'width' => '125px',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);
    }

    private function getFormattedPromotionTypeOptions(): array
    {
        return $this->formatOptions(Promotion::getTypes());
    }

    private function getFormattedPromotionStatusOptions(): array
    {
        return $this->formatOptions(Promotion::getStatuses());
    }

    private function formatOptions(array $options): array
    {
        $formattedOptions = [];
        foreach ($options as $option) {
            $formattedOptions[$option] = $this->formatPromotionTypeOrStatus($option);
        }
        return $formattedOptions;
    }

    private function formatPromotionTypeOrStatus(string $type): string
    {
        $parts = explode('_', $type);
        $formattedParts = array_map(function ($part) {
            return ucfirst(strtolower($part));
        }, $parts);
        return implode(' ', $formattedParts);
    }

    public function callbackColumnActions($value, $row, $column, $isExport): string
    {
        $id = $row->getData('id');
        $method = $row->getData('type') === \Ess\M2ePro\Model\Ebay\Promotion::TYPE_MARKDOWN_SALE
            ? 'openDiscountPopup' : 'updateItemPromotion';

        $addText = __('Add items to Discount');
        $replaceText = __('Replace items in Discount');

        return <<<HTML
<div style="padding: 5px;">
    <select class="admin__control-select"
            style="margin: auto; display: block;"
            onchange="PromotionObj.handleSelectChange(this, '$id', '{$method}')">
        <option value="0"></option>
        <option value="add">{$addText}</option>
        <option value="replace">{$replaceText}</option>
    </select>
</div>
HTML;
    }

    public function callbackFilterType($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value === null) {
            return;
        }

        $collection->addFieldToFilter('type', $value);
    }

    public function callbackFilterStatus($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value === null) {
            return;
        }

        $collection->addFieldToFilter('status', $value);
    }

    protected function getHelpBlockHtml(): string
    {
        if (!$this->canDisplayContainer()) {
            return '';
        }

        $content = __(
            '<p>In this section, you can manage your eBay discounts. Browse your active and scheduled discounts,'
            . ' apply them to your listings, or remove any that are no longer needed. To create a new discount or'
            . ' modify existing ones, you can do so easily through your eBay Seller Hub'
            . ' (<a href="%url" target="_blank" class="external-link">link</a>).</p><br>'
            . ' <p><strong>Important</strong>: Please be aware that adding new items to an existing discount will'
            . ' overwrite any items that were previously included. Due to eBay API restrictions, each discount can'
            . ' include a maximum of 500 items.</p>',
            ['url' => $this->dashboardUrlGenerator->generate($this->marketplaceId)]
        );

        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)
                    ->setData(['content' => $content,])
                    ->toHtml();
    }

    protected function _toHtml(): string
    {
        return $this->getHelpBlockHtml() . parent::_toHtml();
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/ebay_promotion/openGridPromotion', [
            '_current' => true,
            '_query' => [
                'account_id' => $this->accountId,
                'marketplace_id' => $this->marketplaceId,
            ],
        ]);
    }
}
