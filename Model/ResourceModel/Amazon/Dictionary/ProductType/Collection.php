<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Dictionary\ProductType::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::class
        );
    }

    /**
     * @param string $nick
     *
     * @return $this
     */
    public function appendFilterNick(string $nick): self
    {
        $this->getSelect()->where('main_table.nick = ?', $nick);

        return $this;
    }

    /**
     * @param int $marketplaceId
     *
     * @return $this
     */
    public function appendFilterMarketplaceId(int $marketplaceId): self
    {
        $this->getSelect()->where('main_table.marketplace_id = ?', $marketplaceId);

        return $this;
    }

    public function appendFilterInvalid(bool $invalid): self
    {
        $this->getSelect()->where('main_table.invalid = ?', (int)$invalid);

        return $this;
    }

    /**
     * @param array $nicks
     *
     * @return $this
     */
    public function appendFilterNicks(array $nicks): self
    {
        if (empty($nicks)) {
            return $this;
        }

        $this->addFieldToFilter('nick', ['in' => $nicks]);
        return $this;
    }
}
