<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\SynchronizationLog
 */
class SynchronizationLog extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('synchronizationLog');
        $this->_controller = 'adminhtml_developers_tabs_synchronizationLog';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = '';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        // Set template
        // ---------------------------------------
        $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'content' => $this->__(
                '
                <p>This grid displays Synchronization results - Quantity, Price, Details (M2E Pro Listings)<br>
                Synchronization, Orders updating, Marketplaces’ data Synchronization,
                3rd Party Listings Synchronization.</p><br>
                <p>The records about Orders, Inventory and 3rd party Listings Synchronization are
                displayed<br> only in those cases when it results in warning or error messages.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/MAAJAQ#Logs.-SynchronizationLog')
            )
        ]]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
