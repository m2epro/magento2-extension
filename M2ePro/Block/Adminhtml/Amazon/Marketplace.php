<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Marketplace
 */
class Marketplace extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->_controller = 'adminhtml_amazon_marketplace';
        // ---------------------------------------

        $this->removeButton('save');
        $this->removeButton('reset');
        $this->removeButton('back');

        // ---------------------------------------
        $this->addButton('run_update_all', [
            'label' => $this->__('Update All Now'),
            'onclick' => 'MarketplaceObj.updateAction()',
            'class' => 'save update_all_marketplace primary'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('run_synch_now', [
            'label'     => $this->__('Save'),
            'onclick'   => 'MarketplaceObj.saveAction();',
            'class'     => 'save save_and_update_marketplaces primary'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                '<p>This Page contains a list of Amazon international Marketplaces
                where you can sell your Items.</p><br>
                <p><strong>Enable</strong> only those Marketplaces that you want to sell on.
                High number of enabled Marketplaces will take longer to process the necessary data.</p>'
            )
        ]);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        return
                '<div id="marketplaces_progress_bar"></div>' .
                '<div id="marketplaces_content_container">' .
                parent::_toHtml() .
                '</div>';
    }

    //########################################
}
