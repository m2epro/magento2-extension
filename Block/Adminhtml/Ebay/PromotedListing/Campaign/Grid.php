<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\PromotedListing\Campaign;

use Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign as CampaignResource;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign\CollectionFactory $campaignCollectionFactory;
    private \Ess\M2ePro\Model\Ebay\Account $ebayAccount;
    private \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace,
        \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign\CollectionFactory $campaignCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->campaignCollectionFactory = $campaignCollectionFactory;
        $this->ebayAccount = $ebayAccount;
        $this->ebayMarketplace = $ebayMarketplace;
    }

    /** @psalm-suppress UndefinedMagicMethod */
    public function _construct()
    {
        parent::_construct();

        $this->setId('promotedListingCampaignGrid');
        $this->setDefaultSort(CampaignResource::COLUMN_START_DATE);
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->campaignCollectionFactory->create();

        $collection->addFieldToFilter(
            CampaignResource::COLUMN_ACCOUNT_ID,
            ['eq' => (int)$this->ebayAccount->getId()]
        );

        $collection->addFieldToFilter(
            CampaignResource::COLUMN_MARKETPLACE_ID,
            ['eq' => (int)$this->ebayMarketplace->getId()]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('name', [
            'header' => __('Name'),
            'align' => 'left',
            'type' => 'text',
            'index' => CampaignResource::COLUMN_NAME,
            'sortable' => true,
        ]);

        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'center',
            'type' => 'options',
            'filter' => false,
            'index' => CampaignResource::COLUMN_TYPE,
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::TYPE_COST_PER_SALE => __('Cost Per Sale'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::TYPE_COST_PER_CLICK => __('Cost Per Click'),
            ]
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'align' => 'center',
            'type' => 'options',
            'index' => CampaignResource::COLUMN_STATUS,
            'sortable' => true,
            'options' => [
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_DELETED => __('Deleted'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_DRAFT => __('Draft'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_ENDED => __('Ended'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_ENDING_SOON => __('Ending Soon'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_PAUSED => __('Paused'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_PENDING => __('Pending'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_RUNNING => __('Running'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_SCHEDULED => __('Scheduled'),
                \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::STATUS_SYSTEM_PAUSED => __('System Paused'),
            ]
        ]);

        $this->addColumn('rate', [
            'header' => __('Rate'),
            'align' => 'center',
            'type' => 'number',
            'index' => CampaignResource::COLUMN_RATE,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnRate'],
        ]);

        $this->addColumn('start_date', [
            'header' => __('Start Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => CampaignResource::COLUMN_START_DATE,
            'sortable' => true,
        ]);

        $this->addColumn('end_date', [
            'header' => __('End Date'),
            'align' => 'left',
            'type' => 'datetime',
            'index' => CampaignResource::COLUMN_END_DATE,
            'sortable' => true,
            'format' => \IntlDateFormatter::MEDIUM
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'left',
            'type' => 'text',
            'filter' => false,
            'sortable' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'actions' => [
                'addItems' => [
                    'caption' => $this->__('Add Items'),
                    'field' => 'id',
                    'onclick_action' => 'CampaignObj.addItemsToCampaign',
                ],
                'deleteItems' => [
                    'caption' => $this->__('Delete Items'),
                    'field' => 'id',
                    'onclick_action' => 'CampaignObj.deleteItemsFromCampaign',
                ],
                'updateCampaign' => [
                    'caption' => $this->__('Update Campaign'),
                    'field' => 'id',
                    'onclick_action' => 'CampaignObj.openUpdateCampaignPopup',
                ],
                'deleteCampaign' => [
                    'caption' => $this->__('Delete Campaign'),
                    'field' => 'id',
                    'onclick_action' => 'CampaignObj.openDeleteCampaignPopup',
                ],
            ],
        ]);
    }

    /**
     * @param string $value
     * @param \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $row
     * @param $column
     * @param $isExport
     *
     * @return string
     */
    public function callbackColumnRate($value, $row, $column, $isExport)
    {
        return (string)round((float)$value, 1);
    }

    protected function _toHtml()
    {
        return $this->getMessagesContainer() . parent::_toHtml();
    }

    private function getMessagesContainer(): string
    {
        return '<div id="campaign_grid_messages"></div>';
    }
}
