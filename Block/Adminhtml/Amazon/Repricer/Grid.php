<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Repricer;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    /** @var \Ess\M2ePro\Helper\View */
    private $viewHelper;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\View $viewHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
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
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Repricer\Grid
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Repricer\Grid
     * @throws \Exception
     */
    protected function _prepareColumns(): Grid
    {
        $this->addColumn('id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'id',
            'sortable' => false,
            'filter' => false,
        ]);

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

        $this->addColumn('total_products', [
            'header' => __('Repricing products'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'total_products',
            'sortable' => false,
            'filter' => false,
        ]);

        $this->addColumn('m2e_products', [
            'header' => __('M2E Pro products'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'id',
            'sortable' => false,
            'filter' => false,
            'frame_callback' => [$this, 'callbackColumnM2eProducts'],
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'linked',
            'sortable' => false,
            'filter' => false,
            'frame_callback' => [$this, 'callbackColumnStatus'],
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _toHtml(): string
    {
        $this->jsTranslator->add('Please enter correct value.', __('Please enter correct value.'));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Repricer'));

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Repricer',
    ], function(){
        window.AmazonRepricerObj = new AmazonRepricer();
    });
JS
        );

        return parent::_toHtml();
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
        if ($item->getData('linked')) {
            return $this->viewHelper->getUrl($item, 'repricer_settings', 'edit', ['id' => $item->getData('id')]);
        }
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
     * @param $collection
     * @param $column
     *
     * @return void
     */
    protected function callbackFilterTitle($collection, $column): void
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

    /**
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function callbackColumnM2eProducts($value, $row, $column, $isExport): int
    {
        $listingProductObject = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product'
        );

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
        $collection = $listingProductObject->getCollection();

        $collection->getSelect()->join(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            '(`l`.`id` = `main_table`.`listing_id`)',
            []
        );

        $collection->getSelect()->where('`second_table`.`is_variation_parent` = 0');
        $collection->getSelect()->where('`second_table`.`is_repricing` = 1');
        $collection->getSelect()->where('`l`.`account_id` = ?', $row->getData('id'));

        return $collection->getSize();
    }

    /**
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     *
     * @return string
     */
    public function callbackColumnStatus($value, $row, $column, $isExport): string
    {
        if ($row->getData('linked')) {
            return '<span style="color: green;">Connected</span>';
        }

        return '<span style="color: gray;">Not Connected</span>';
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
        $id = $row->getId();

        if ($row->getData('linked')) {
            $onclick = "AmazonRepricerObj.unlinkRepricing('{$id}')";
            $label = 'Disconnect';
            $class = 'action-secondary';
        } else {
            $onclick = "AmazonRepricerObj.linkOrRegisterRepricing('{$id}')";
            $label = 'Connect';
            $class = 'action-primary';
        }

        return <<<HTML
    <a href="javascript:void(0)" onclick="{$onclick}" class="{$class}" style="text-decoration: none">
    {$label}
    </a>
HTML;
    }
}
