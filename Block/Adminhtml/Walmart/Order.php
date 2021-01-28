<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Order
 */
class Order extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_walmart_order';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton(
            'upload_by_user',
            [
                'label'   => $this->__('Order Reimport'),
                'onclick' => 'UploadByUserObj.openPopup()',
                'class'   => 'action-primary'
            ]
        );
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock(
            [
                'content' => $this->__(
                    <<<HTML
                <p>On this page, you can review the Channel Sales imported from Walmart.
                In the grid below, filter the records to narrow your search results,
                then click the Order line to review the details. Use the Action menu to manage
                each Channel Order individually or update them in bulk.
                </p><br>
                <p><strong>Note:</strong> you can enable an automatic creation of Magento Orders, Invoices,
                and Shipments in the Account Configuration under
                <i>Walmart Integration > Configuration > Accounts > Edit Account > Orders</i>.</p>
HTML
                )
            ]
        );

        $this->setPageActionsBlock('Walmart_Order_PageActions');

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        return $this->createBlock('Order_Item_Edit')->toHtml() .
            parent::getGridHtml();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        $this->js->addRequireJs(
            ['upload' => 'M2ePro/Order/UploadByUser'],
            <<<JS
    UploadByUserObj = new UploadByUser('walmart', 'orderUploadByUserPopupGrid');
JS
        );

        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Order_UploadByUser')
        );

        $this->jsTranslator->addTranslations(
            [
                'Order Reimport'               => $this->__('Order Reimport'),
                'Order importing in progress.' => $this->__('Order importing in progress.'),
                'Order importing is canceled.' => $this->__('Order importing is canceled.')
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
