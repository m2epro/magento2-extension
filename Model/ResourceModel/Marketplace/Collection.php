<?php

namespace Ess\M2ePro\Model\ResourceModel\Marketplace;

/**
 * @method  \Ess\M2ePro\Model\Marketplace[] getItems()
 * @method  \Ess\M2ePro\Model\Marketplace getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Marketplace::class,
            \Ess\M2ePro\Model\ResourceModel\Marketplace::class
        );
    }

    public function appendFilterEnabledMarketplaces(string $component): self
    {
        $this->addFieldToFilter('component_mode', $component);
        $this->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        return $this;
    }
}
