<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Wizard
 */
abstract class Wizard extends \Ess\M2ePro\Controller\Adminhtml\Wizard
{
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($nameBuilder, $context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart');
    }

    //########################################

    protected function getCustomViewNick()
    {
        return \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    protected function getMenuRootNodeNick()
    {
        return \Ess\M2ePro\Helper\View\Walmart::MENU_ROOT_NODE_NICK;
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        parent::initResultPage();

        $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
    }

    protected function getMenuRootNodeLabel()
    {
        return $this->getHelper('View\Walmart')->getMenuRootNodeLabel();
    }

    //########################################

    protected function indexAction()
    {
        if ($this->isSkipped()) {
            return $this->_redirect('*/walmart_listing/index/');
        }

        return parent::indexAction();
    }

    //########################################
}
