<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Indexer\VariationParent;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Indexer\VariationParent\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    const INDEXER_LIFETIME = 1800;

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->listing = $listing;
        $this->activeRecordFactory = $activeRecordFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function prepare()
    {
        if ($this->isUpToDate()) {
            return;
        }

        $resourceModel = $this->activeRecordFactory->getObject(
            ucfirst($this->listing->getComponentMode()) . '\Listing\Product\Indexer\VariationParent'
        )->getResource();
        $resourceModel->clear($this->listing->getId());
        $resourceModel->build($this->listing);

        $this->markAsIsUpToDate();
    }

    public function markInvalidated()
    {
        $this->getHelper('Data_Cache_Permanent')->removeValue(
            $this->getUpToDateCacheKey()
        );
        return $this;
    }

    //########################################

    private function isUpToDate()
    {
        return $this->getHelper('Data_Cache_Permanent')->getValue(
            $this->getUpToDateCacheKey()
        );
    }

    private function markAsIsUpToDate()
    {
        $this->getHelper('Data_Cache_Permanent')->setValue(
            $this->getUpToDateCacheKey(),
            'true',
            ['listing_product_indexer_variation_parent'],
            self::INDEXER_LIFETIME
        );
        return $this;
    }

    private function getUpToDateCacheKey()
    {
        return '_listing_product_indexer_variation_parent_up_to_date_for_listing_id_' . $this->listing->getId();
    }

    //########################################
}
