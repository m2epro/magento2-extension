<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

class Account extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_SERVER_HASH = 'server_hash';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_IDENTIFIER = 'identifier';
    public const COLUMN_INFO = 'info';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_account', 'account_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
