<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

use Ess\M2ePro\Controller\Adminhtml\Context;

abstract class Wizard extends \Ess\M2ePro\Controller\Adminhtml\Wizard
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($nameBuilder, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay');
    }

    //########################################

    protected function initResultPage()
    {
        if (!is_null($this->resultPage)) {
            return;
        }

        parent::initResultPage();

        $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
    }

    protected function getCustomViewNick()
    {
        return \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    protected function getMenuRootNodeNick()
    {
        return \Ess\M2ePro\Helper\View\Ebay::MENU_ROOT_NODE_NICK;
    }

    protected function getMenuRootNodeLabel()
    {
        return $this->getHelper('View\Ebay')->getMenuRootNodeLabel();
    }

    //########################################
}