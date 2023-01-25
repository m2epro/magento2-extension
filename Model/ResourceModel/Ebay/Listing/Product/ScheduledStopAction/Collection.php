<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Product\ScheduledStopAction::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction::class
        );
    }

    public function appendFilterListingProductId(int $listingProductId): self
    {
        $this->addFieldToFilter('listing_product_id', $listingProductId);

        return $this;
    }

    public function appendFilterCreateDateLessThan(\DateTime $date): self
    {
        $this->addFieldToFilter('create_date', ['lt' => $date->format('Y-m-d H:i:s')]);

        return $this;
    }

    public function appendFilterNotProcessed(): self
    {
        $this->addFieldToFilter('process_date', ['null' => true]);

        return $this;
    }

    public function deleteOldScheduledStopActions(\DateTime $expirationDate): void
    {
        $condition = [
            'create_date < \'' . $expirationDate->format('Y-m-d H:i:s' . '\'')
        ];

        $this->getConnection()->delete($this->getMainTable(), $condition);
    }

    public function setLimit(int $limit): self
    {
        $this->getSelect()->limit($limit);

        return $this;
    }
}
