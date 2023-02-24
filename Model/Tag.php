<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use Ess\M2ePro\Model\ResourceModel\Tag as Resource;

class Tag extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const NICK_HAS_ERROR = 'has_error';
    public const NICK_EBAY_MISSING_ITEM_SPECIFIC = 'missing_item_specific';

    /**
     * @inerhitDoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Tag::class);
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return $this->getDataByKey(Resource::NICK_FIELD);
    }
}
