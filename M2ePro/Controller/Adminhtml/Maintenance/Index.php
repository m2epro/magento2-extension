<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Maintenance;

use \Magento\Backend\App\Action;
use \Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

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
        $block->setTemplate('Ess_M2ePro::maintenance.phtml');

        $this->_addContent($block);

        return $result;
    }

    private function isMigration()
    {
        $tableName = $this->helperFactory->getObject('Module_Database_Structure')
                                         ->getTableNameWithPrefix('core_config_data');
        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from($tableName, 'value')
                                           ->where('scope = ?', 'default')
                                           ->where('scope_id = ?', 0)
                                           ->where('path = ?', BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH);

        $currentWizardStep = $this->resourceConnection->getConnection()->fetchOne($select);

        if ($currentWizardStep === BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED ||
            $currentWizardStep === BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS
        ) {
            return true;
        }

        return false;
    }

    //########################################
}
