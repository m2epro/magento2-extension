<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

abstract class Wizard extends \Ess\M2ePro\Controller\Adminhtml\Wizard
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\View\Amazon */
    protected $amazonViewHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);

        $this->amazonFactory = $amazonFactory;
        $this->amazonViewHelper = $amazonViewHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon');
    }

    protected function getCustomViewNick()
    {
        return \Ess\M2ePro\Helper\View\Amazon::NICK;
    }

    protected function getMenuRootNodeNick()
    {
        return \Ess\M2ePro\Helper\View\Amazon::MENU_ROOT_NODE_NICK;
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
        return $this->amazonViewHelper->getMenuRootNodeLabel();
    }

    protected function indexAction()
    {
        if ($this->isSkipped()) {
            return $this->_redirect('*/amazon_listing/index/');
        }

        return parent::indexAction();
    }
}
