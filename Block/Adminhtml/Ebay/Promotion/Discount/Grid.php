<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Promotion\Discount;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('promotionDiscountGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();

        $promotionId = $this->globalDataHelper->getValue('promotionId');

        $collection->addFieldToFilter('main_table.promotion_id', $promotionId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
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

    public function callbackColumnName($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $id = $row->getData('id');
        $text = __('Assign');

        $html = <<<HTML
    <a href="javascript:void(0);" onclick="PromotionObj.updateMarkdownPromotion('$id')">{$text}</a>
HTML;

        return $html;
    }

    protected function getHelpBlockHtml()
    {
        $helpBlockHtml = '';

        if ($this->canDisplayContainer()) {
            $content = __(
                <<<HTML
                <p>some text</p>
HTML
                ,
                '#'
            );

            $helpBlockHtml = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
                'content' => $content,
            ])->toHtml();
        }

        return $helpBlockHtml;
    }

    protected function _toHtml()
    {
        return $this->getHelpBlockHtml() . parent::_toHtml();
    }
}
