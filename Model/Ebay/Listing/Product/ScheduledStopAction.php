<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class ScheduledStopAction extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction::class);
    }

    public function setListingProductId(int $listingProductId): self
    {
        $this->setData('listing_product_id', $listingProductId);

        return $this;
    }

    public function getListingProductId(): int
    {
        return (int)$this->getData('listing_product_id');
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->setData('create_date', $createDate->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getCreateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            (string)$this->getData('create_date')
        );
    }

    public function setProcessDate(\DateTime $processDate): self
    {
        $this->setData('process_date', $processDate->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getProcessDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            (string)$this->getData('process_date')
        );
    }
}
