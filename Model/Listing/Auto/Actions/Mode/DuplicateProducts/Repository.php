<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
    ) {
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->listingResource = $listingResource;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param \Magento\Catalog\Model\Product $magentoProduct
     *
     * @return int[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListingProductIds(
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\Catalog\Model\Product $magentoProduct
    ): array {
        $collection = $this->listingProductCollectionFactory->create();

        $collection->addFieldToSelect('id', 'listing_product_id');
        $collection->addFieldToSelect('product_id');
        $collection->getSelect()->join(
            ['lst' => $this->listingResource->getMainTable()],
            'lst.id = main_table.listing_id',
            ['marketplace_id', 'account_id']
        );

        $collection->addFieldToFilter('main_table.product_id', $magentoProduct->getId());
        $collection->addFieldToFilter('main_table.component_mode', $listing->getComponentMode());
        $collection->addFieldToFilter('lst.account_id', $listing->getAccountId());
        $collection->addFieldToFilter('lst.marketplace_id', $listing->getMarketplaceId());

        $result = $collection->toArray();

        if ($result['totalRecords'] === 0) {
            return [];
        }

        return array_map(function ($item) {
            return (int)$item['listing_product_id'];
        }, $result['items']);
    }
}
