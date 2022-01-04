<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Helper\Component\Walmart\Vocabulary
 */
class Vocabulary extends \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary
{
    protected $walmartParentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartParentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->walmartParentFactory = $walmartParentFactory;
        parent::__construct($modelFactory, $activeRecordFactory, $helperFactory, $context);
    }

    //########################################

    public function addAttribute($productAttribute, $channelAttribute)
    {
        if (!parent::addAttribute($productAttribute, $channelAttribute)) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToAttribute($channelAttribute);
        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    public function addOption($productOption, $channelOption, $channelAttribute)
    {
        if (!parent::addOption($productOption, $channelOption, $channelAttribute)) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToOption(
            $channelAttribute,
            $channelOption
        );

        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    //########################################

    public function getParentListingsProductsAffectedToAttribute($channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);

        $collection->addFieldToFilter(
            'additional_data',
            ['regexp' => '"variation_channel_attributes":.*"'.$channelAttribute.'"']
        );

        return $collection->getItems();
    }

    public function getParentListingsProductsAffectedToOption($channelAttribute, $channelOption)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->addFieldToFilter('additional_data', [
            'regexp'=> '"variation_channel_options":.*"'.$channelAttribute.'":"'.$channelOption.'"}']);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'second_table.variation_parent_id'
        ]);

        $parentIds = $collection->getColumnValues('variation_parent_id');
        if (empty($parentIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);
        $collection->addFieldToFilter('id', ['in' => $parentIds]);

        return $collection->getItems();
    }

    //########################################
}
