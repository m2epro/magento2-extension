<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction
 */
class Instruction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_product_instruction', 'id');
    }

    //########################################

    public function add(array $instructionsData)
    {
        if (empty($instructionsData)) {
            return;
        }

        $listingsProductsIds = [];

        foreach ($instructionsData as $instructionData) {
            $listingsProductsIds[] = $instructionData['listing_product_id'];
        }

        $listingsProductsCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', ['in' => array_unique($listingsProductsIds)]);

        foreach ($instructionsData as $index => &$instructionData) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($instructionData['listing_product_id']);
            if ($listingProduct === null) {
                unset($instructionsData[$index]);
                continue;
            }

            $instructionData['component']   = $listingProduct->getComponentMode();
            $instructionData['create_date'] = $this->helperFactory->getObject('Data')->getCurrentGmtDate();
        }

        $this->getConnection()->insertMultiple($this->getMainTable(), $instructionsData);
    }

    public function remove(array $instructionsIds)
    {
        if (empty($instructionsIds)) {
            return;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                'id IN (?)' => $instructionsIds,
                'skip_until IS NULL OR ? > skip_until' => $this->helperFactory->getObject('Data')->getCurrentGmtDate()
            ]
        );
    }

    //########################################
}
