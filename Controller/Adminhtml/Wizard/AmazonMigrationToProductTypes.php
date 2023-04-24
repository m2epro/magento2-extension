<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

abstract class AmazonMigrationToProductTypes extends \Ess\M2ePro\Controller\Adminhtml\Wizard
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /**
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Magento\Framework\Code\NameBuilder $nameBuilder
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);
        $this->magentoHelper = $magentoHelper;
    }

    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed($this->getMenuRootNodeNick());
    }

    /**
     * @return string
     */
    protected function getNick(): string
    {
        return \Ess\M2ePro\Model\Wizard\AmazonMigrationToProductTypes::NICK;
    }

    protected function getCustomViewNick()
    {
        return null;
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        parent::initResultPage();

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Amazon Integration updates')
        );
        $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
    }

    /**
     * @return string
     */
    protected function getMenuRootNodeNick(): string
    {
        return \Ess\M2ePro\Helper\View\Amazon::MENU_ROOT_NODE_NICK;
    }

    protected function getMenuRootNodeLabel()
    {
        return $this->__('M2E Pro Amazon Integration updates');
    }

    protected function congratulationAction()
    {
        if (!$this->isFinished()) {
            return $this->_redirect('*/*/index');
        }

        $this->magentoHelper->clearMenuCache();

        return $this->_redirect('*/amazon_listing/index');
    }
}
