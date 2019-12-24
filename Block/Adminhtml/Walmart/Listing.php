<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing
 */
class Listing extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_walmart_listing';

        // ---------------------------------------
        $url = $this->getUrl('*/walmart_listing_create/index', [
            'step' => '1',
            'clear' => 'yes'
        ]);
        $this->addButton('add', [
            'label'     => $this->__('Add Listing'),
            'onclick'   => 'setLocation(\'' . $url . '\')',
            'class'     => 'action-primary'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = $this->__(
            'On this page, you can review M2E Pro Listings that you set up. <br>
            In the grid below, click M2E Pro Listing line to manage the Products placed to that Listing. <br>
            Use the Action menu next to the Listing to manage its Products, Settings or Logs. The Mass Actions allows
            clearing Logs and/or deleting Listings in bulk.'
        );

        $this->appendHelpBlock([
            'content' => $content
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
