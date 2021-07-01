<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Mapping;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Mapping\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected $_template = 'listing/mapping/view.phtml';

    public function _construct()
    {
        $this->_controller = 'adminhtml_listing_mapping';

        parent::_construct();
    }

    protected function _beforeToHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->addData(
            [
                'content' => $this->__(
                    'From the list below you should select a Magento Product to which you would like the Item
                 to be linked. Click on Link To This Product link to set accordance.'
                )
            ]
        );
        $this->setChild('help_block', $helpBlock);

        /** @var \Ess\M2ePro\Block\Adminhtml\Listing\Mapping\Grid $block */
        $block = $this->createBlock(
            'Listing_Mapping_Grid',
            '',
            [
                'data' => [
                    'grid_url'           => $this->getData('grid_url'),
                    'mapping_handler_js' => $this->getData('mapping_handler_js'),
                    'mapping_action'     => $this->getData('mapping_action')
                ]
            ]
        );

        $this->setChild('listing_mapping_grid', $block);

        parent::_beforeToHtml();
    }

    //########################################
}
