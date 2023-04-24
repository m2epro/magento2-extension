<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace::class
        );
    }

    /**
     * @param int $marketplaceId
     *
     * @return $this
     */
    public function appendFilterMarketplaceId(int $marketplaceId): self
    {
        $this->getSelect()->where('marketplace_id = ?', $marketplaceId);

        return $this;
    }
}
