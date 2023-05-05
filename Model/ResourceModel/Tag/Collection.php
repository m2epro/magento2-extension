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
            \Ess\M2ePro\Model\Tag\Entity::class,
            \Ess\M2ePro\Model\ResourceModel\Tag::class
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Tag\Entity[]
     */
    public function getItemsWithoutHasErrorsTag(): array
    {
        $this->getSelect()->where('error_code != (?)', \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE);

        return $this->getAll();
    }

    /**
     * @return \Ess\M2ePro\Model\Tag\Entity[]
     */
    public function getAll(): array
    {
        return $this->getItems();
    }
}
