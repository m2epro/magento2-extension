<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Indexer\Listing\Product;

class VariationParent extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Indexer\Listing\Product\VariationParent');
    }

    //########################################
}