<?php

namespace Ess\M2ePro\Model\Walmart\AdvancedFilter;

use Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown as DropDownFilter;

class AllOrdersOptions
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order */
    private $orderResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order */
    private $walmartOrderResource;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionCollectionFactory */
    private $optionCollectionFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionFactory */
    private $optionFactory;

    public function __construct(
        DropDownFilter\OptionCollectionFactory $optionCollectionFactory,
        DropDownFilter\OptionFactory $optionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Order $orderResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Order $walmartOrderResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource
    ) {
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->orderResource = $orderResource;
        $this->walmartOrderResource = $walmartOrderResource;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
    }

    public function getAccountOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['account' => $this->accountResource->getMainTable()],
            'account.id = account_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['account.id', 'account.title']);
        $select->order(['account.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getMarketplaceOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'marketplace.id = marketplace_id',
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );
        $select->group(['marketplace.id', 'marketplace.title']);
        $select->order(['marketplace.title']);

        $optionsData = $select->query()->fetchAll();

        $optionCollection = $this->optionCollectionFactory->create();
        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    public function getYesNoOptions(): DropDownFilter\OptionCollection
    {
        $optionsData = [
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')],
        ];

        $optionCollection = $this->optionCollectionFactory->create();

        foreach ($optionsData as $optionData) {
            $option = $this->optionFactory->create(
                $optionData['label'],
                $optionData['value']
            );
            $optionCollection->addOption($option);
        }

        return $optionCollection;
    }

    private function getBaseSelect(): \Magento\Framework\DB\Select
    {
        $select = $this->listingResource->getConnection()->select();
        $select->from(
            ['orders' => $this->orderResource->getMainTable()],
            []
        );
        $select->joinInner(
            ['walmart_order' => $this->walmartOrderResource->getMainTable()],
            'walmart_order.order_id = order_id',
            []
        );

        $select->where('orders.component_mode = ?', \Ess\M2ePro\Helper\Component\Walmart::NICK);

        return $select;
    }
}
