<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\General;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class CreateAttribute extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_general';
        $this->_mode = 'createAttribute';

        // Initialization block
        // ---------------------------------------
        $this->setId('generalCreateAttribute');
        // ---------------------------------------
    }

    //########################################
}