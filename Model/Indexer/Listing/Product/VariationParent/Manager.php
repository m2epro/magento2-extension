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
    private $listing = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setListing($value)
    {
        if (!($value instanceof \Ess\M2ePro\Model\Listing)) {
            $value = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $value);
        }
        $this->listing = $value;
        return $this;
    }

    //########################################

    public static function getTrackedFields()
    {
        return [
            'online_price',
            'online_sale_price',
            'online_sale_price_start_date',
            'online_sale_price_end_date'
        ];
    }

    //########################################

    public function prepare()
    {
        if ($this->isUpToDate()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Indexer\Listing\Product\VariationParent $resourceModel */
        $resourceModel = $this->activeRecordFactory->getObject(
            'Indexer\Listing\Product\VariationParent'
        )->getResource();
        $resourceModel->clear($this->listing->getId());
        $resourceModel->build($this->listing->getId(), $this->listing->getComponentMode());

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