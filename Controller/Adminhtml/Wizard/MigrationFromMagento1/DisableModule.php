<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1\DisableModule
 */
class DisableModule extends \Magento\Backend\App\Action
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory  */
    protected $resultPageFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->helperFactory = $helperFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);

        if ($wizard->isStarted()) {
            return $this->_redirect('*/wizard_migrationFromMagento1/database');
        }

        $result = $this->resultPageFactory->create();

        $result->getConfig()->addPageAsset("Ess_M2ePro::css/style.css");
        $result->getConfig()->addPageAsset("Ess_M2ePro::css/wizard.css");

        $result->getConfig()->getTitle()->set(__(
            'M2E Pro Module Migration from Magento v1.x'
        ));

        /** @var \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\DisableModule $block */
        $block = $result->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\DisableModule::class
        );
        $block->setData('nick', \Ess\M2ePro\Model\Wizard\MigrationFromMagento1::NICK);

        $this->_addContent($block);

        return $result;
    }

    //########################################
}
