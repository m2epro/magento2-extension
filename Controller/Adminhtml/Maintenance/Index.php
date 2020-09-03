<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Maintenance;

use \Magento\Backend\App\Action;
use \Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Maintenance\Index
 */
class Index extends Action
{
    private $helperFactory;
    private $resourceConnection;
    private $pageFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Context $controllerContext,
        Action\Context $context
    ) {
        $this->helperFactory = $controllerContext->getHelperFactory();
        $this->resourceConnection = $controllerContext->getResourceConnection();
        $this->pageFactory = $controllerContext->getResultPageFactory();
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        if (!$this->helperFactory->getObject('Module\Maintenance')->isEnabled()) {
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
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        return $wizard->isStarted();
    }

    //########################################
}
