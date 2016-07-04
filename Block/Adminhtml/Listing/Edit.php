<?php
/**
 * Created by PhpStorm.
 * User: HardRock
 * Date: 11.03.2016
 * Time: 19:49
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_listing';

        parent::_construct();
    }
} 