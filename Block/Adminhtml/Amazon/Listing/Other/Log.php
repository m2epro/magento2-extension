<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Log extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingOtherLog');
        $this->_controller = 'adminhtml_amazon_listing_other_log';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------

        if (!$this->getListingOtherId()) {
            $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
        }
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------
    }

    // ---------------------------------------

    public function getListingOtherId()
    {
        $otherListingData = $this->getHelper('Data\GlobalData')->getValue('temp_data');
        return isset($otherListingData['id']);
    }

    //########################################

    protected function _prepareLayout()
    {
        if ($this->getListingOtherId()) {
            $this->appendHelpBlock([
                'content' => $this->__('
                This Log contains all information about Actions, which were done on 3rd Party Listings.'
                )
            ]);
        }

        return parent::_prepareLayout();
    }

    //########################################
}