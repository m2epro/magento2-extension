<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

/**
 * Class \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\AbstractMode
 */
abstract class AbstractMode extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var null|\Magento\Catalog\Model\Product
     */
    private $product = null;

    protected $activeRecordFactory;
    protected $parentFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getProduct()
    {
        if (!($this->product instanceof \Magento\Catalog\Model\Product)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "Product" should be set first.');
        }

        return $this->product;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
     */
    protected function getListingObject(\Ess\M2ePro\Model\Listing $listing)
    {
        $componentMode = ucfirst($listing->getComponentMode());

        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Listing $object */
        $object = $this->modelFactory->getObject($componentMode.'\Listing\Auto\Actions\Listing');

        $object->setListing($listing);

        return $object;
    }

    //########################################

    /**
     * Preventing duplicate products in listings in one channel account and a marketplace via auto-adding
     *
     * @param \Ess\M2ePro\Model\Listing provided $listing
     * @return bool
     */
    protected function existsDuplicateListingProduct($listing)
    {
        $collection = $this->activeRecordFactory->getObject('Listing_Product')->getCollection();

        $collection->getSelect()
            ->join(
                ['lst' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                'lst.id = main_table.listing_id',
                ['marketplace_id' => 'marketplace_id', 'account_id' => 'account_id']
            )
            ->where(
                'lst.account_id = ' . $listing->getAccountId() .
                ' AND lst.marketplace_id = ' . $listing->getMarketplaceId()
            );

        $collection->addFieldToFilter('main_table.component_mode', $listing->getComponentMode());
        $collection->addFieldToFilter('lst.account_id', $listing->getAccountId());
        $collection->addFieldToFilter('lst.marketplace_id', $listing->getMarketplaceId());

        foreach ($collection->getItems() as $listingProduct) {
            if ($this->getProduct()->getId() == $listingProduct->getProductId()) {
                $this->writeDuplicateProductLog($listing->getComponentMode(), $listing->getId(), $listingProduct->getId());

                return true;
            }
        }

        return false;
    }

    //########################################

    /**
     * @param string $componentMode
     * @param int $listingId
     * @param int $listingProductId
     */
    private function writeDuplicateProductLog($componentMode, $listingId, $listingProductId)
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode($componentMode);

        $logModel->addProductMessage(
            $listingId,
            $this->getProduct()->getId(),
            $listingProductId,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $logModel->getResource()->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_LISTING,
            'Product was not added since the item is already presented in another Listing related to ' .
            'the Channel account and marketplace.',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
        );
    }

    //########################################
}
