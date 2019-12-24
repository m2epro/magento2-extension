<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Module
 */
class Module extends Command
{
    //########################################

    /**
     * @title "Run All"
     * @description "Emulate starting cron"
     * @new_line
     */
    public function runCronAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Cron was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Cron was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Issues Resolver"
     * @description "Process Issues Resolver Task"
     */
    public function issuesResolverAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\IssuesResolver::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Issues Resolver Task was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Issues Resolver Task was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Health Status Notifications"
     * @description "Process Health Status"
     */
    public function healthStatusNotificationsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\HealthStatus::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Health Status was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Health Status was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Archive Orders Entities"
     * @description "Process Archive Orders Entities Task"
     */
    public function archiveOrdersEntitiesAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\ArchiveOrdersEntities::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Archive Orders Entities was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Archive Orders Entities was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Logs Clearing"
     * @description "Process Logs Clearing Task"
     * @new_line
     */
    public function processLogsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\LogsClearing::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Logs Clearing was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Logs Clearing was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Synchronization"
     * @description "Process Synchronization Task"
     */
    public function synchronizationAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Synchronization::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Synchronization was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Synchronization was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Servicing"
     * @description "Process Servicing Task"
     * @new_line
     */
    public function processServicingAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Servicing::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Servicing was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "eBay Actions"
     * @description "Process eBay Actions Task"
     */
    public function ebayActionsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Ebay\Actions::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('eBay Actions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('eBay Actions was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "eBay Update Account Preferences"
     * @description "Process Account Preferences Task"
     * @new_line
     */
    public function ebayAccountPreferencesAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('eBay Update Account Preferences was successfully performed.');
        } else {
            $this->getMessageManager()->addError('eBay Update Account Preferences was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Amazon Actions"
     * @description "Process Amazon Actions Task"
     * @new_line
     */
    public function amazonActionsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Amazon\Actions::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Amazon Actions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Amazon Actions was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Walmart Actions"
     * @description "Process Walmart Actions Task"
     * @new_line
     */
    public function walmartActionsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Walmart\Actions::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Walmart Actions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Walmart Actions was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Request Pending Single"
     * @description "Process Request Pending Single Task"
     */
    public function requestPendingSingleAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\RequestPendingSingle::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Request Pending Single was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Request Pending Single was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Request Pending Partial"
     * @description "Process Request Pending Partial Task"
     */
    public function requestPendingPartialAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Request Pending Partial was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Request Pending Partial was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Connector Pending Single"
     * @description "Process Connector Pending Single Task"
     */
    public function connectorPendingSingleAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Connector Pending Single was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Connector Pending Single was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Connector Pending Partial"
     * @description "Process Connector Pending Partial Task"
     * @new_line
     */
    public function connectorPendingPartialAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Connector Pending Partial was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Connector Pending Partial was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Repricing Update Settings"
     * @description "Process Repricing Update Settings"
     */
    public function repricingUpdateSettingsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingUpdateSettings::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Send Data was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Send Data was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Repricing Synchronization General"
     * @description "Process Repricing Synchronization General"
     */
    public function repricingSynchronizationGeneralAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationGeneral::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Synchronization General was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Synchronization General was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Repricing Synchronization Actual Price"
     * @description "Process Repricing Synchronization Actual Price"
     */
    public function repricingSynchronizationActualPriceAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationActualPrice::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()
                ->addSuccess('Repricing Synchronization Actual Price was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Synchronization Actual Price was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Repricing Inspect Products"
     * @description "Process Repricing Inspect Products Task"
     */
    public function repricingRepricingInspectProductsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron_Runner_Developer');
        $cronRunner->setAllowedTasks([
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingInspectProducts::NICK
        ]);

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Inspect Products was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Inspect Products was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################
}
