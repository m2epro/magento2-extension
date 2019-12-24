<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Indexer\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Indexer\Listing\Product\VariationParent
 */
class VariationParent extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Indexer\Listing\Product\VariationParent');
    }

    //########################################
}
