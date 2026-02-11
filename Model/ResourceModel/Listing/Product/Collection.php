<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

use Ess\M2ePro\Model\ResourceModel\Listing\Product as ListingProductResource;

/**
 * @method \Ess\M2ePro\Model\Listing\Product[] getItems()
 * @method \Ess\M2ePro\Model\Listing\Product getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing\Product::class,
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::class
        );
    }

    /**
     * @return void
     */
    public function selectListingId(): void
    {
        $this->addFieldToSelect(ListingProductResource::LISTING_ID_FIELD);
    }

    /**
     * @param string|int $value
     *
     * @return void
     */
    public function whereProductIdEq($value): void
    {
        $this->getSelect()->where(ListingProductResource::PRODUCT_ID_FIELD . ' = ?', $value);
    }

    public function whereComponentMode(string $componentMode): void
    {
        $this->getSelect()->where(ListingProductResource::COMPONENT_MODE_FIELD . ' = ?', $componentMode);
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function joinListingTable($columns = [])
    {
        $this->getSelect()->join(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            '(`l`.`id` = `main_table`.`listing_id`)',
            $columns
        );

        return $this;
    }
}
