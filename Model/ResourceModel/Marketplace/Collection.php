<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Marketplace;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Marketplace::class,
            \Ess\M2ePro\Model\ResourceModel\Marketplace::class
        );
    }

    /**
     * @param string $component
     *
     * @return $this
     */
    public function appendFilterEnabledMarketplaces(string $component): self
    {
        $this->addFieldToFilter('component_mode', $component);
        $this->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        return $this;
    }
}
