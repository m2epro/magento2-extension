<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Maintenance;

use \Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

class Index extends \Magento\Backend\App\Action
{
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $moduleMaintenanceHelper;

    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    /** @var \Magento\Framework\View\Result\PageFactory */
    private $pageFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $controllerContext,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->pageFactory = $controllerContext->getResultPageFactory();
        $this->moduleMaintenanceHelper = $moduleMaintenanceHelper;
        $this->wizardHelper = $wizardHelper;
    }

    public function execute()
    {
        if (!$this->moduleMaintenanceHelper->isEnabled()) {
            return $this->_redirect('admin');
        }

        $result = $this->pageFactory->create();

        $result->getConfig()->getTitle()->set(__(
            'M2E Pro is currently under maintenance'
        ));
        $this->_setActiveMenu('Ess_M2ePro::m2epro_maintenance');

        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $result->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setData('is_migration', $this->isMigration());
        $block->setData('migration_wizard_url', $this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        $block->setTemplate('Ess_M2ePro::maintenance.phtml');

        $this->_addContent($block);

        return $result;
    }

    private function isMigration()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->wizardHelper->getWizard(MigrationFromMagento1::NICK);
        return $wizard->isStarted();
    }
}
