<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Marketplace extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->_controller = 'adminhtml_walmart_marketplace';
        // ---------------------------------------

        $this->removeButton('save');
        $this->removeButton('reset');
        $this->removeButton('back');

        // ---------------------------------------
        $this->addButton('run_synch_now', [
            'label' => __('Save'),
            'onclick' => 'MarketplaceObj.saveAction();',
            'class' => 'save save_and_update_marketplaces primary',
        ]);
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => __(
                '<p>In this section, you can set up Walmart Marketplaces you will work with.
                Enable the required Marketplaces and press <strong>Save</strong>. The Marketplace data will be
                downloaded and synchronized with your M2E Pro installation.</p><br>
                <p>It is recommended to update Marketplaces when any related changes are announced by Walmart.
                To do it, press <strong>Update Now</strong>.</p><br>
                <p><strong>Note:</strong> installation and update processes might be a time-consuming depending
                on the number of enabled Marketplaces and your server environment.</p><br>'
            ),
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
}
