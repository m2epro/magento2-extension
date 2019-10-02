<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

/**
 * Class Index
 * @package Ess\M2ePro\Controller\Adminhtml\Support
 */
class Index extends Support
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('Support\Form'));

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Contact Us'));

        return $this->getResult();
    }

    //########################################
}
