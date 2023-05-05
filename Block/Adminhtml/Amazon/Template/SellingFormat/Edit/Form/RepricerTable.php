<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class RepricerTable extends AbstractGrid
{
    /** @var \Ess\M2ePro\Helper\View */
    private $viewHelper;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\View $viewHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->viewHelper = $viewHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _construct(): void
    {
        parent::_construct();

        // Initialize view
        // ---------------------------------------
        $view = $this->viewHelper->getCurrentView();
        // ---------------------------------------

        // Initialization block
        // ---------------------------------------
        $this->setId($view . 'RepricerGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        // ---------------------------------------
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _prepareCollection(): RepricerTable
    {
        $collection = $this->amazonFactory->getObject('Account')->getCollection();

        $collection->getSelect()->joinLeft(
            [
                'm' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable(),
            ],
            '(`m`.`id` = `second_table`.`marketplace_id`)',
            ['marketplace_title' => 'title']
        );

        $collection->getSelect()->joinLeft(
            [
                'r' => $this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getResource()->getMainTable(),
            ],
            '(`r`.`account_id` = `main_table`.`id`)',
            [
                'linked' => 'r.account_id',
                'total_products' => 'total_products',
            ]
        );

        $collection->addFieldToFilter('r.account_id', ['notnull' => true]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit\Form\RepricerTable
     * @throws \Exception
     */
    protected function _prepareColumns(): RepricerTable
    {
        $this->addColumn('title', [
            'header' => __('Title / Info'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'escape' => true,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'sortable' => false,
            'filter' => false,
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'center',
            'width' => '100px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'getter' => 'getId',
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/accountGrid', ['_current' => true]);
    }

    /**
     * @param $item
     *
     * @return string|void
     */
    public function getRowUrl($item)
    {
        return '';
    }

    /**
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnTitle($value, $row, $column, $isExport): string
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

    /**
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     *
     * @return string
     */
    public function callbackColumnActions($value, $row, $column, $isExport): string
    {
        $url = $this->viewHelper->getUrl(
            $row,
            'repricer_settings',
            'edit',
            ['id' => $row->getData('id'), 'close_on_save' => true]
        );

        return <<<HTML
    <a href="{$url}" class="action-primary" style="text-decoration: none" target="_blank">Go to Settings</a>
HTML;
    }
}
