<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing;

/**
 * Class Log
 * @package Ess\M2ePro\Model\Ebay\Listing
 */
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
