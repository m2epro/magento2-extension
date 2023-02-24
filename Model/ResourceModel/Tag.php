<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

class Tag extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const ID_FIELD = 'id';
    public const NICK_FIELD = 'nick';

    /**
     * @inerhitDoc
     */
    protected function _construct()
    {
        $this->_init('m2epro_tag', self::ID_FIELD);
    }
}
