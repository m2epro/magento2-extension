<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_other', 'id');
    }

    //########################################

    public function getItemsByProductId($productId, array $filters = [])
    {
        $cacheKey   = __METHOD__.$productId.sha1($this->getHelper('Data')->jsonEncode($filters));
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $select = $this->getConnection()
            ->select()
            ->from(
                $this->getMainTable(),
                ['id','component_mode']
            )
            ->where("`product_id` IS NOT NULL AND `product_id` = ?", (int)$productId);

        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                $select->where('`'.$column.'` = ?', $value);
            }
        }

        $result = [];

        foreach ($select->query()->fetchAll() as $item) {
            $result[] = $this->parentFactory->getObjectLoaded(
                $item['component_mode'],
                'Listing\Other',
                (int)$item['id']
            );
        }

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $result);

        return $result;
    }

    //########################################
}
