<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Exception;

class Connection extends \Ess\M2ePro\Model\Exception
{
    //########################################

    public function __construct($message, $additionalData = array())
    {
        parent::__construct($message, $additionalData, 0, false);
    }

    //########################################
}