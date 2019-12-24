<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

use Ess\M2ePro\Model\Magento\Product\Inventory\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Inventory
 */
class Inventory extends AbstractModel
{
    //########################################

    /**
     * @return bool|int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isInStock()
    {
        return $this->getStockItem()->getIsInStock();
    }

    /**
     * @return float|mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getQty()
    {
        return $this->getStockItem()->getQty();
    }

    //########################################
}