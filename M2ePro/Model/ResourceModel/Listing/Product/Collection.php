<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Listing\Product',
            'Ess\M2ePro\Model\ResourceModel\Listing\Product'
        );
    }

    //########################################

    public function joinListingTable($columns = [])
    {
        $this->getSelect()->join(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            '(`l`.`id` = `main_table`.`listing_id`)',
            $columns
        );

        return $this;
    }

    //########################################
}
