<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category;

use Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category as ResourceCategory;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing\Auto\Category::class,
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category::class
        );
    }

    /**
     * @return void
     */
    public function selectCategoryId(): void
    {
        $this->addFieldToSelect(ResourceCategory::CATEGORY_ID_FIELD);
    }

    /**
     * @return void
     */
    public function selectGroupId(): void
    {
        $this->addFieldToSelect(ResourceCategory::GROUP_ID_FIELD);
    }

    /**
     * @param array $value
     *
     * @return void
     */
    public function whereCategoryIdIn(array $value): void
    {
        $this->getSelect()->where(ResourceCategory::CATEGORY_ID_FIELD . ' IN (?)', $value);
    }
}
