<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Wizard;

abstract class MigrationFromMagento1 extends Wizard
{
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    protected $ebayViewHelper;

    /** @var \Ess\M2ePro\Helper\View\Amazon */
    protected $amazonViewHelper;

    /** @var \Ess\M2ePro\Helper\View\Walmart */
    protected $walmartViewHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);

        $this->ebayViewHelper = $ebayViewHelper;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->walmartViewHelper = $walmartViewHelper;
    }

    protected function init()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Module Migration from Magento v1.x')
        );
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        parent::initResultPage();

        if ($this->getMenuRootNodeNick() !== null) {
            $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
        }
    }

    protected function getNick()
    {
        return \Ess\M2ePro\Model\Wizard\MigrationFromMagento1::NICK;
    }

    protected function getCustomViewNick()
    {
        return null;
    }

    protected function getMenuRootNodeNick()
    {
        $referrer = $this->getRequest()->getParam('referrer');

        if ($referrer == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return \Ess\M2ePro\Helper\View\Ebay::MENU_ROOT_NODE_NICK;
        }
        if ($referrer == \Ess\M2ePro\Helper\View\Amazon::NICK) {
            return \Ess\M2ePro\Helper\View\Amazon::MENU_ROOT_NODE_NICK;
        }
        if ($referrer == \Ess\M2ePro\Helper\View\Walmart::NICK) {
            return \Ess\M2ePro\Helper\View\Walmart::MENU_ROOT_NODE_NICK;
        }

        return null;
    }

    protected function getMenuRootNodeLabel()
    {
        $referrer = $this->getRequest()->getParam('referrer');

        switch ($referrer) {
            case \Ess\M2ePro\Helper\View\Ebay::NICK:
                return $this->ebayViewHelper->getMenuRootNodeLabel();
            case \Ess\M2ePro\Helper\View\Amazon::NICK:
                return $this->amazonViewHelper->getMenuRootNodeLabel();
            case \Ess\M2ePro\Helper\View\Walmart::NICK:
                return $this->walmartViewHelper->getMenuRootNodeLabel();
        }

        return null;
    }

    protected function isInstallationWizardFinished()
    {
        return true;
    }

    protected function createCongratulationBlock()
    {
        return $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation::class
        )
        ->setData(['nick' => $this->getNick()]);
    }
}
