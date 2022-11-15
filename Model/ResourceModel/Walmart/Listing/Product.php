<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $walmartFactory;

    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->listingProductResource = $listingProductResource;

        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $parentFactory,
            $context,
            $connectionName
        );
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function mapChannelItemProduct(\Ess\M2ePro\Model\Walmart\Listing\Product $listingProduct)
    {
        $walmartItemTable = $this->activeRecordFactory->getObject('Walmart\Item')->getResource()->getMainTable();

        $existedRelation = $this->getConnection()
            ->select()
            ->from(['ei' => $walmartItemTable])
            ->where('`account_id` = ?', $listingProduct->getListing()->getAccountId())
            ->where('`marketplace_id` = ?', $listingProduct->getListing()->getMarketplaceId())
            ->where('`sku` = ?', $listingProduct->getSku())
            ->where('`product_id` = ?', $listingProduct->getParentObject()->getProductId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $this->getConnection()->update(
            $walmartItemTable,
            ['product_id' => $listingProduct->getParentObject()->getProductId()],
            [
                'account_id = ?' => $listingProduct->getListing()->getAccountId(),
                'marketplace_id = ?' => $listingProduct->getListing()->getMarketplaceId(),
                'sku = ?' => $listingProduct->getSku(),
                'product_id = ?' => $listingProduct->getParentObject()->getOrigData('product_id')
            ]
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function moveChildrenToListing(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->join(
            ['wlp' => $this->getMainTable()],
            'lp.id = wlp.listing_product_id',
            null
        );
        $select->join(
            ['parent_lp' => $this->listingProductResource->getMainTable()],
            'parent_lp.id = wlp.variation_parent_id',
            ['listing_id' => 'parent_lp.listing_id']
        );
        $select->where('wlp.variation_parent_id = ?', $listingProduct->getId());

        $updateQuery = $connection->updateFromSelect(
            $select,
            ['lp' => $this->listingProductResource->getMainTable()]
        );

        $connection->query($updateQuery);
    }

    //########################################
}
