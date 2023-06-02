<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Account\Grid
{
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($supportHelper, $viewHelper, $context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class));

        $this->jsTranslator->addTranslations([
            'The specified Title is already used for other Account. Account Title must be unique.' => __(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
            'No Customer entry is found for specified ID.' => __(
                'No Customer entry is found for specified ID.'
            ),
            'If Yes is chosen, you must select at least one Attribute for Product Linking.' => __(
                'If Yes is chosen, you must select at least one Attribute for Product Linking.'
            ),
            'is_ready_for_document_generation' => __(
                <<<HTML
    To use this option, go to <i>Stores > Configuration > General > General > Store Information</i> and fill in the
    following required fields:<br><br>
        <ul style="padding-left: 50px">
            <li>Store Name</li>
            <li>Country</li>
            <li>ZIP/Postal Code</li>
            <li>City</li>
            <li>Street Address</li>
        </ul>
HTML
            )
        ]);

        $this->jsUrl->addUrls([
            '*/amazon_account/delete/' => $this->getUrl('*/amazon_account/delete/'),
        ]);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        window.AmazonAccountObj = new AmazonAccount();
    });
JS
        );
    }

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Account')->getCollection();

        $collection->getSelect()->joinLeft(
            [
                'm' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable(),
            ],
            '(`m`.`id` = `second_table`.`marketplace_id`)',
            ['marketplace_title' => 'title']
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'id',
            'filter_index' => 'main_table.id',
        ]);

        $this->addColumn('title', [
            'header' => __('Title / Info'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'escape' => true,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Account $row */
        $marketplaceLabel = __('Marketplace');
        $marketplaceTitle = $row->getData('marketplace_title');

        $merchantLabel = __('Merchant ID');
        $merchantId = $row->getChildObject()->getData('merchant_id');

        return <<<HTML
<div>
    {$value}<br/>
    <span style="font-weight: bold">{$merchantLabel}</span>:
    <span style="color: #505050">{$merchantId}</span>
    <br/>
    <span style="font-weight: bold">{$marketplaceLabel}</span>:
    <span style="color: #505050">{$marketplaceTitle}</span>
    <br/>
</div>
HTML;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $delete = __('Delete');

        return <<<HTML
<div>
    <a class="action-default" href="javascript:" onclick="AmazonAccountObj.deleteClick('{$row->getId()}')">
        {$delete}
    </a>
</div>
HTML;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR m.title LIKE ? OR second_table.merchant_id LIKE ?',
            '%' . $value . '%'
        );
    }
}
