<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
 */
abstract class Main extends \Ess\M2ePro\Controller\Adminhtml\Main
{
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        Context $context
    ) {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart');
    }

    //########################################

    protected function getCustomViewNick()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        parent::initResultPage();

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->getHelper('Component\Walmart')->getTitle()
        );

        if ($this->getLayoutType() != self::LAYOUT_BLANK) {
            $this->getResultPage()->setActiveMenu(\Ess\M2ePro\Helper\View\Walmart::MENU_ROOT_NODE_NICK);
        }
    }

    //########################################
}
