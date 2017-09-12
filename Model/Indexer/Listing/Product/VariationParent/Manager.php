<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Indexer\Listing\Product\VariationParent;

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
    )
    {
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

        /** @var \Ess\M2ePro\Model\ResourceModel\Indexer\Listing\Product\VariationParent $resourceModel */
        $resourceModel = $this->activeRecordFactory->getObject(
            ucfirst($this->listing->getComponentMode()) . '\Indexer\Listing\Product\VariationParent'
        )->getResource();
        $resourceModel->clear($this->listing->getId());
        $resourceModel->build($this->listing);

        $this->markAsIsUpToDate();
    }

    public function markInvalidated()
    {
        $this->getHelper('Data\Cache\Permanent')->removeValue(
            $this->getUpToDateCacheKey()
        );
        return $this;
    }

    //########################################

    private function isUpToDate()
    {
        return $this->getHelper('Data\Cache\Permanent')->getValue(
            $this->getUpToDateCacheKey()
        );
    }

    private function markAsIsUpToDate()
    {
        $this->getHelper('Data\Cache\Permanent')->setValue(
            $this->getUpToDateCacheKey(),
            'true',
            ['indexer_listing_product_parent'],
            self::INDEXER_LIFETIME
        );
        return $this;
    }

    private function getUpToDateCacheKey()
    {
        return '_indexer_listing_product_parent_up_to_date_for_listing_id_' . $this->listing->getId();
    }

    //########################################
}