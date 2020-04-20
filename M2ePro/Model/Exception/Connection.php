<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Exception;

/**
 * Class \Ess\M2ePro\Model\Exception\Connection
 */
class Connection extends \Ess\M2ePro\Model\Exception
{
    //########################################

    public function __construct($message, $additionalData = [])
    {
        parent::__construct($message, $additionalData, 0, false);
    }

    //########################################
}
