<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Tag;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    /**
     * @inerhitDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Tag::class,
            \Ess\M2ePro\Model\ResourceModel\Tag::class
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Tag[]
     */
    public function getAll(): array
    {
        return $this->getItems();
    }
}
