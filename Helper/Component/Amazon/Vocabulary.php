<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

/**
 * Class \Ess\M2ePro\Helper\Component\Amazon\Vocabulary
 */
class Vocabulary extends \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary
{
    protected $amazonParentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonParentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->amazonParentFactory = $amazonParentFactory;
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
            'Amazon_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
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
            'Amazon_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    //########################################

    public function getParentListingsProductsAffectedToAttribute($channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $existListingProductCollection */
        $existListingProductCollection = $this->amazonParentFactory->getObject('Listing\Product')->getCollection();
        $existListingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $existListingProductCollection->addFieldToFilter('general_id', ['notnull' => true]);

        $existListingProductCollection->getSelect()->where(
            'additional_data NOT REGEXP ?',
            '"variation_matched_attributes":{.+}'
        );
        $existListingProductCollection->addFieldToFilter(
            'additional_data',
            ['regexp'=> '"variation_channel_attributes_sets":.*"'.$channelAttribute.'":']
        );

        $affectedListingsProducts = $existListingProductCollection->getItems();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $newListingProductCollection = $this->amazonParentFactory->getObject('Listing\Product')->getCollection();
        $newListingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $newListingProductCollection->addFieldToFilter('is_general_id_owner', 1);
        $newListingProductCollection->addFieldToFilter('general_id', ['null' => true]);

        $newListingProductCollection->getSelect()->where(
            'additional_data NOT REGEXP ?',
            '"variation_channel_theme":\s*".*"'
        );

        /** @var \Ess\M2ePro\Model\Listing\Product[] $newListingsProducts */
        $newListingsProducts = $newListingProductCollection->getItems();

        if (empty($newListingsProducts)) {
            return $affectedListingsProducts;
        }

        $productRequirementsCache = [];

        foreach ($newListingsProducts as $newListingProduct) {
            if (isset($affectedListingsProducts[$newListingProduct->getId()])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct      = $newListingProduct->getChildObject();
            $amazonDescriptionTemplate = $amazonListingProduct->getAmazonDescriptionTemplate();

            $productAttributes = $amazonListingProduct->getVariationManager()->getTypeModel()->getProductAttributes();
            if (empty($productAttributes)) {
                continue;
            }

            if (isset($productRequirementsCache[$amazonDescriptionTemplate->getId()][count($productAttributes)])) {
                $affectedListingsProducts[$newListingProduct->getId()] = $newListingProduct;
                continue;
            }

            $marketplaceDetails = $this->modelFactory->getObject('Amazon_Marketplace_Details');
            $marketplaceDetails->setMarketplaceId($newListingProduct->getListing()->getMarketplaceId());

            $productDataNick = $amazonDescriptionTemplate->getProductDataNick();

            foreach ($marketplaceDetails->getVariationThemes($productDataNick) as $themeNick => $themeData) {
                $themeAttributes = $themeData['attributes'];

                if (count($themeAttributes) != count($productAttributes)) {
                    continue;
                }

                if (!in_array($channelAttribute, $themeAttributes)) {
                    continue;
                }

                $affectedListingsProducts[$newListingProduct->getId()] = $newListingProduct;
                $productRequirementsCache[$amazonDescriptionTemplate->getId()][count($productAttributes)] = true;

                break;
            }
        }

        return $affectedListingsProducts;
    }

    public function getParentListingsProductsAffectedToOption($channelAttribute, $channelOption)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonParentFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $listingProductCollection->addFieldToFilter('general_id', ['notnull' => true]);

        $listingProductCollection->addFieldToFilter(
            'additional_data',
            ['regexp'=> '"variation_matched_attributes":{.+}']
        );
        $listingProductCollection->addFieldToFilter(
            'additional_data',
            ['regexp'=>
                '"variation_channel_attributes_sets":.*"'.$channelAttribute.'":\s*[\[|{].*'.$channelOption.'.*[\]|}]'
            ]
        );

        return $listingProductCollection->getItems();
    }

    //########################################
}
