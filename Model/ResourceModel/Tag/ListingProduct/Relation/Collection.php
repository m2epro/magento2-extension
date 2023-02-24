<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation;

use Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation as ResourceModel;
use Ess\M2ePro\Model\Tag\ListingProduct\Relation;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    /**
     * @inerhitDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(Relation::class, ResourceModel::class);
    }
}
