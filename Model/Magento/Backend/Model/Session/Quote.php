<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Backend\Model\Session;

/**
 * Class Quote
 * @package Ess\M2ePro\Model\Magento\Backend\Model\Session
 */
class Quote extends \Magento\Backend\Model\Session\Quote
{
    public function clearStorage()
    {
        parent::clearStorage();
        $this->_quote = null;
        return $this;
    }
}
