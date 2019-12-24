<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Indexer\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Walmart\Indexer\Listing\Product\VariationParent
 */
class VariationParent extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Indexer\Listing\Product\VariationParent');
    }

    //########################################
}
