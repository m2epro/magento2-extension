<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing;

class Log extends \Ess\M2ePro\Model\Listing\Log
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    //########################################
}