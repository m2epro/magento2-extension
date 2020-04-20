<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\Listing\Product\Instruction',
            'Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction'
        );
    }

    //########################################

    /**
     * @param \DateTime|NULL $dateTime
     * @return $this
     */
    public function applySkipUntilFilter($dateTime = null)
    {
        $dateTime === null && $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->getSelect()->where(
            'skip_until IS NULL OR ? > skip_until',
            $dateTime->format('Y-m-d H:i:s')
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function applyNonBlockedFilter()
    {
        $this->getSelect()
            ->joinLeft(
                ['pl' => $this->activeRecordFactory->getObject('Processing_Lock')->getResource()->getMainTable()],
                'pl.object_id = main_table.listing_product_id AND model_name = \'Listing_Product\''
            );

        $this->addFieldToFilter('pl.id', ['null' => true]);
        return $this;
    }

    //########################################
}
