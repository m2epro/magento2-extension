<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\VersionDowngrade;

use Ess\M2ePro\Model\Wizard\VersionDowngrade;

class Index extends \Magento\Backend\App\Action
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;
    /** @var \Ess\M2ePro\Model\Wizard\VersionDowngrade */
    private $versionDowngradeWizard;
    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory */
    private $resultPageFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Model\Wizard\VersionDowngrade $versionDowngradeWizard,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->magentoHelper = $magentoHelper;
        $this->wizardHelper = $wizardHelper;
        $this->versionDowngradeWizard = $versionDowngradeWizard;
        $this->resultPageFactory = $resultPageFactory;
    }

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    public function execute()
    {
        if (!$this->versionDowngradeWizard->isVersionDowngrade()) {
            $this->versionDowngradeWizard->finishRepairProcess();
            $this->magentoHelper->clearMenuCache();

            return $this->_redirect('admin/dashboard');
        }

        if (
            $this->wizardHelper->isNotStarted(VersionDowngrade::NICK)
            || $this->wizardHelper->isActive(VersionDowngrade::NICK)
        ) {
            $result = $this->resultPageFactory->create();

            $result->getConfig()->addPageAsset("Ess_M2ePro::css/style.css");
            $result->getConfig()->addPageAsset("Ess_M2ePro::css/wizard.css");

            $result->getConfig()->getTitle()->set(
                __(
                    'Critical issue detected: M2ePro module files have been deployed in a lower version'
                )
            );

            /** @var \Ess\M2ePro\Block\Adminhtml\Wizard\VersionDowngrade\Content $block */
            $block = $result->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Wizard\VersionDowngrade\Content::class
            );

            $this->_addContent($block);

            /** @var \Ess\M2ePro\Block\Adminhtml\General $generalBlock */
            $generalBlock = $result->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
            $result->getLayout()->setChild('js', $generalBlock->getNameInLayout(), '');

            return $result;
        }

        return $this->_redirect('admin/dashboard');
    }
}
