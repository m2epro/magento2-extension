<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order;

class Log extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('orderLog');
        $this->_controller = 'adminhtml_order_log';
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
        // ---------------------------------------

        $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
    }

    //########################################

    protected function _toHtml()
    {
        $componentNick = $this->getHelper('Data\GlobalData')->getValue('component_nick');
        $marketplaceFilterBlock = $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => $componentNick,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);
        $marketplaceFilterBlock->setUseConfirm(false);

        $accountFilterBlock = $this->createBlock('Account\Switcher')->setData([
            'component_mode' => $componentNick,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);
        $accountFilterBlock->setUseConfirm(false);

        $pageActionsHtml = '';
        $marketplaceFilterHtml = $marketplaceFilterBlock->toHtml();
        $accountFilterHtml = $accountFilterBlock->toHtml();
        if (trim($marketplaceFilterHtml) || trim($accountFilterHtml)) {
            $pageActionsHtml = '<div class="page-main-actions"><div class="filter_block">'
            . $marketplaceFilterBlock->toHtml()
            . $accountFilterBlock->toHtml()
            . '</div></div>';
        }

        return $pageActionsHtml . parent::_toHtml();
    }

    //########################################
}