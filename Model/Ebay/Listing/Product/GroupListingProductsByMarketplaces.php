<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class GroupListingProductsByMarketplaces
{
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing $listingResource;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->listingProductResource = $listingProductResource;
        $this->listingResource = $listingResource;
        $this->marketplaceResource = $marketplaceResource;
    }

    /**
     * @return list<array{
     *     marketplace_id: int,
     *     marketplace_title: string,
     *     listing_product_ids: int[]
     * }>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $ids): array
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(['lp' => $this->listingProductResource->getMainTable()], []);
        $select->where('lp.id IN (?)', $ids);
        $select->where('lp.component_mode = ?', 'ebay');
        $select->join(['l' => $this->listingResource->getMainTable()], 'lp.listing_id = l.id', []);
        $select->join(['m' => $this->marketplaceResource->getMainTable()], 'l.marketplace_id = m.id', []);
        $select->columns([
            'marketplace_id' => 'm.id',
            'marketplace_title' => 'm.title',
            'listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT lp.id)'),
        ]);
        $select->group(['m.id', 'm.title']);

        $result = array_values($select->query()->fetchAll());

        array_walk($result, function (&$item) {
            $item['marketplace_id'] = (int)$item['marketplace_id'];
            $item['listing_product_ids'] = array_map(
                'intval',
                explode(
                    ',',
                    $item['listing_product_ids']
                )
            );
        });

        return $result;
    }
}
