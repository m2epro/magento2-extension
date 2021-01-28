<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\UploadByUser;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Order\UploadByUser\Popup
 */
class Popup extends AbstractContainer
{
    /** @var string */
    protected $_component;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('orderUploadByUserGrid');

        $this->_controller = 'adminhtml_order_uploadByUser';
        $this->_headerText = '';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
    }

    //########################################

    public function getGridHtml()
    {
        $this->getChildBlock('grid')->setComponent($this->_component);

        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'content' => $this->__(
                <<<HTML
M2E Pro provides an automatic order synchronization as basic functionality.
Use manual order import as an alternative only in <a href="%url%" target="_blank">these cases</a>.
HTML
                ,
                $this->getHelper('Module_Support')->getHowToGuideUrl('1594828')
            ),
            'style'   => 'margin-top: 15px;',
            'title'   => $this->__('Order Reimport')
        ]]);

        return '<div id="uploadByUser_messages" style="margin-top: 10px;"></div>' .
               $helpBlock->toHtml() .
               parent::getGridHtml();
    }

    //########################################

    public function setComponent($component)
    {
        $this->_component = $component;
    }

    //########################################
}
