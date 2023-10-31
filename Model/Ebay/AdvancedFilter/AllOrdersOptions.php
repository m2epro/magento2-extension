<?php

namespace Ess\M2ePro\Model\Ebay\AdvancedFilter;

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
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order */
    private $ebayOrderResource;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionCollectionFactory */
    private $optionCollectionFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionFactory */
    private $optionFactory;

    public function __construct(
        DropDownFilter\OptionCollectionFactory $optionCollectionFactory,
        DropDownFilter\OptionFactory $optionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Order $orderResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order $ebayOrderResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource
    ) {
        $this->marketplaceResource = $marketplaceResource;
        $this->accountResource = $accountResource;
        $this->listingResource = $listingResource;
        $this->orderResource = $orderResource;
        $this->ebayOrderResource = $ebayOrderResource;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
    }

    public function getAccountOptions(): DropDownFilter\OptionCollection
    {
        $select = $this->getBaseSelect();
        $select->joinInner(
            ['account' => $this->accountResource->getMainTable()],
            'account.id = orders.account_id',
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
            ['ebay_order' => $this->ebayOrderResource->getMainTable()],
            'ebay_order.order_id = orders.id',
            []
        );

        $select->where('orders.component_mode = ?', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        return $select;
    }
}
