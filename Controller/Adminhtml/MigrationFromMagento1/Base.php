<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use \Magento\Backend\App\Action;
use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

use \Ess\M2ePro\Helper\Factory as HelperFactory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Base
 */
abstract class Base extends Action
{
    protected $currentWizardStep = null;

    /** @var HelperFactory $helperFactory */
    protected $helperFactory = null;

    /** @var \Magento\Framework\Controller\Result\RawFactory $resultRawFactory  */
    protected $resultRawFactory = null;

    /** @var \Magento\Framework\App\ResourceConnection|null  */
    protected $resourceConnection = null;

    /** @var \Magento\Framework\Controller\Result\Raw $rawResult  */
    protected $rawResult = null;

    //########################################

    public function __construct(\Ess\M2ePro\Controller\Adminhtml\Context $context)
    {
        $this->helperFactory = $context->getHelperFactory();
        $this->resultRawFactory = $context->getResultRawFactory();
        $this->resourceConnection = $context->getResourceConnection();

        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    protected function getRawResult()
    {
        if ($this->rawResult === null) {
            $this->rawResult = $this->resultRawFactory->create();
        }

        return $this->rawResult;
    }

    //########################################

    protected function __()
    {
        return $this->getHelper('Module\Translation')->translate(func_get_args());
    }

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################

    protected function getCurrentWizardStatus()
    {
        if ($this->currentWizardStep === null) {
            $select = $this->resourceConnection->getConnection()
                ->select()
                ->from(
                    $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                    'value'
                )
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH);

            $this->currentWizardStep = $this->resourceConnection->getConnection()->fetchOne($select);
        }

        return $this->currentWizardStep;
    }

    public function setWizardStatus($status)
    {
        if ($this->getCurrentWizardStatus() === false) {
            $this->resourceConnection->getConnection()->insert(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                [
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH,
                    'value'    => $status
                ]
            );
        } else {
            $this->resourceConnection->getConnection()->update(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                ['value' => $status],
                [
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH,
                ]
            );
        }

        $this->currentWizardStep = null;
    }

    //########################################
}
