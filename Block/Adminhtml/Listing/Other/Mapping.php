<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Other;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Other\Mapping
 */
class Mapping extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected $_template = 'listing/other/mapping.phtml';

    //########################################

    public function _construct()
    {
        $this->_controller = 'adminhtml_listing_other_mapping';

        parent::_construct();
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $data = [
            'id'    => 'mapping_submit_button',
            'label' => $this->__('Confirm'),
            'class' => 'mapping_submit_button submit'
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('mapping_submit_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $this->setChild(
            'mapping_grid',
            $this->createBlock('Listing_Other_Mapping_Grid')
        );
        // ---------------------------------------

        // ---------------------------------------
        $helpBlock = $this->createBlock('HelpBlock')->addData([
            'content' => $this->__(
                'From the list below you should select a Magento Product to which you would like the Item
                 to be linked. Click on Map To This Product link to set accordance.'
            )
        ]);
        $this->setChild('help_block', $helpBlock);
        // ---------------------------------------

        parent::_beforeToHtml();
    }

    //########################################
}
