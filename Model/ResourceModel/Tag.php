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
    public const ERROR_CODE_FIELD = 'error_code';
    public const TEXT_FIELD = 'text';
    public const CREATE_DATE_FIELD = 'create_date';

    protected function _construct()
    {
        $this->_init('m2epro_tag', self::ID_FIELD);
    }
}
