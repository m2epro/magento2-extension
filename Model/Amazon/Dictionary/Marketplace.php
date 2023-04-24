<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace::class);
    }

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getData('marketplace_id');
    }

    public function setMarketplaceId(int $marketplaceId): self
    {
        $this->setData('marketplace_id', $marketplaceId);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getClientDetailsLastUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData('client_details_last_update_date'));
    }

    public function setClientDetailsLastUpdateDate(\DateTime $value): self
    {
        $this->setData('client_details_last_update_date', $value->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getServerDetailsLastUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData('server_details_last_update_date'));
    }

    public function setServerDetailsLastUpdateDate(\DateTime $value): self
    {
        $this->setData('server_details_last_update_date', $value->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypes(): array
    {
        return $this->getSettings('product_types');
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setProductTypes(array $productTypes): self
    {
        $this->setSettings('product_types', $productTypes);

        return $this;
    }
}
