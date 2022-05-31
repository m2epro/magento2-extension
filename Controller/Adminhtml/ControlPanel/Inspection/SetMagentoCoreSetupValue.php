<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class SetMagentoCoreSetupValue extends Main
{
    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    protected $moduleResource;

    /** @var \Ess\M2ePro\Helper\View\ControlPanel */
    protected $controlPanelHelper;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext,
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($dbContext);
        $this->controlPanelHelper = $controlPanelHelper;
    }

    public function execute()
    {
        $version = $this->getRequest()->getParam('version');
        if (!$version) {
            $this->messageManager->addWarning('Version is not provided.');
            return $this->_redirect($this->controlPanelHelper->getPageUrl());
        }

        $version = str_replace(',', '.', $version);
        if (!version_compare(\Ess\M2ePro\Model\Setup\Upgrader::MIN_SUPPORTED_VERSION_FOR_UPGRADE, $version, '<=')) {
            $this->messageManager->addError(
                sprintf(
                    'Extension upgrade can work only from %s version.',
                    \Ess\M2ePro\Model\Setup\Upgrader::MIN_SUPPORTED_VERSION_FOR_UPGRADE
                )
            );
            return $this->_redirect($this->controlPanelHelper->getPageUrl());
        }

        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);

        $this->messageManager->addSuccess($this->__('Extension upgrade was completed.'));
        return $this->_redirect($this->controlPanelHelper->getPageUrl());
    }
}
