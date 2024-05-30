<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Promotion;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->globalDataHelper = $globalDataHelper;
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
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection(): Grid
    {
        $collection = $this->collectionFactory->create();

        $accountId = $this->globalDataHelper->getValue('accountId');
        $marketplaceId = $this->globalDataHelper->getValue('marketplaceId');

        $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        $collection->addFieldToFilter('main_table.account_id', $accountId);

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
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnName'],
        ]);

        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'right',
            'type' => 'text',
            'width' => '75px',
            'index' => 'type',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnType'],
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'align' => 'right',
            'type' => 'text',
            'width' => '75px',
            'index' => 'status',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ]);

        $this->addColumn('start_date', [
            'header' => __('Start Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => 'start_date',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter' => false,
            'sortable' => false,
        ]);

        $this->addColumn('end_date', [
            'header' => __('End Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => 'end_date',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter' => false,
            'sortable' => false,
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

    public function callbackColumnName($value, $row, $column, $isExport): string
    {
        return $value;
    }

    public function callbackColumnType($value, $row, $column, $isExport): string
    {
        return $this->formatPromotionTypeOrStatus($value);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport): string
    {
        return $this->formatPromotionTypeOrStatus($value);
    }

    public function callbackColumnActions($value, $row, $column, $isExport): string
    {
        $id = $row->getData('id');
        $method = $row->getData('type') === \Ess\M2ePro\Model\Ebay\Promotion::TYPE_MARKDOWN_SALE
            ? 'openDiscountPopup' : 'updateItemPromotion';

        $addText = __('Add items to Promotion');
        $replaceText = __('Replace items in Promotion');

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

    private function formatPromotionTypeOrStatus(string $type): string
    {
        $parts = explode('_', $type);

        $formattedParts = array_map(function ($part) {
            return ucfirst(strtolower($part));
        }, $parts);

        return implode(' ', $formattedParts);
    }

    protected function getHelpBlockHtml(): string
    {
        $helpBlockHtml = '';

        if ($this->canDisplayContainer()) {
            $content = __(
                <<<HTML
                <p>In this section, you can manage your eBay promotions. Browse your active and scheduled promotions,
                apply them to your listings, or remove any that are no longer needed. To create a new promotion or
                modify existing ones, you can do so easily through your eBay Seller Hub
                (<a href="%1" target="_blank" class="external-link">link</a>).</p><br>
                <p><strong>Important</strong>: Please be aware that adding new items to an existing promotion will
                overwrite any items that were previously included. Due to eBay API restrictions, each promotion can
                include a maximum of 500 items.</p>
HTML
                ,
                'https://www.ebay.com/sh/mkt/promotionmanager/dashboard'
            );

            $helpBlockHtml = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
                'content' => $content,
            ])->toHtml();
        }

        return $helpBlockHtml;
    }

    protected function _toHtml(): string
    {
        return $this->getHelpBlockHtml() . parent::_toHtml();
    }
}
