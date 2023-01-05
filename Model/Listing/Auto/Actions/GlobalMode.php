<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

class GlobalMode
{
    /** @var \Magento\Catalog\Model\Product */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory */
    private $autoActionsListingFactory;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts */
    private $duplicateProducts;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    public function __construct(
        \Magento\Catalog\Model\Product $magentoProduct,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory $autoActionsListingFactory,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts $duplicateProducts,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    ) {
        $this->magentoProduct = $magentoProduct;
        $this->autoActionsListingFactory = $autoActionsListingFactory;
        $this->duplicateProducts = $duplicateProducts;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function synch(): void
    {
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        $collection->addFieldToFilter('auto_mode', \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL);
        $collection->addFieldToFilter(
            'auto_global_adding_mode',
            ['neq' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE]
        );

        foreach ($collection->getItems() as $listing) {
            /** @var \Ess\M2ePro\Model\Listing $listing */
            if (!$listing->isAutoGlobalAddingAddNotVisibleYes()) {
                if (
                    $this->magentoProduct->getVisibility()
                    == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
                ) {
                    continue;
                }
            }

            if ($this->duplicateProducts->checkDuplicateListingProduct($listing, $this->magentoProduct)) {
                continue;
            }

            $autoActionListing = $this->autoActionsListingFactory->create($listing);
            $autoActionListing->addProductByGlobalListing(
                $this->magentoProduct,
                $listing
            );
        }
    }
}
