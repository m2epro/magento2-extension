<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

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
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Cron was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Cron was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Issues Resolver"
     * @description "Process Issues Resolver Task"
     */
    public function issuesResolverAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\IssuesResolver::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Issues Resolver Task was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Issues Resolver Task was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Synchronization"
     * @description "Process Synchronization Task"
     */
    public function synchronizationAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Synchronization::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Synchronization was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Synchronization was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Servicing"
     * @description "Process Servicing Task"
     */
    public function processServicingAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Servicing::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Servicing was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Logs Clearing"
     * @description "Process Logs Clearing Task"
     */
    public function processLogsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\LogsClearing::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Logs Clearing was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Logs Clearing was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process eBay Actions"
     * @description "Process eBay Actions Task"
     */
    public function ebayActionsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Ebay\Actions::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('eBay Actions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('eBay Actions was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Amazon Actions"
     * @description "Process Amazon Actions Task"
     */
    public function amazonActionsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Amazon\Actions::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Amazon Actions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Amazon Actions was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Request Pending Single"
     * @description "Process Request Pending Single Task"
     */
    public function requestPendingSingleAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\RequestPendingSingle::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Request Pending Single was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Request Pending Single was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Request Pending Partial"
     * @description "Process Request Pending Partial Task"
     */
    public function requestPendingPartialAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Request Pending Partial was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Request Pending Partial was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Connector Pending Single"
     * @description "Process Connector Pending Single Task"
     */
    public function connectorPendingSingleAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Connector Pending Single was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Connector Pending Single was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Connector Pending Partial"
     * @description "Process Connector Pending Partial Task"
     */
    public function connectorPendingPartialAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Connector Pending Partial was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Connector Pending Partial was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################

    /**
     * @title "Process Repricing Update Settings"
     * @description "Process Repricing Update Settings"
     */
    public function repricingUpdateSettingsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingUpdateSettings::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Send Data was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Send Data was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Repricing Synchronization General"
     * @description "Process Repricing Synchronization General"
     */
    public function repricingSynchronizationGeneralAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationGeneral::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Synchronization General was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Synchronization General was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Repricing Synchronization Actual Price"
     * @description "Process Repricing Synchronization Actual Price"
     */
    public function repricingSynchronizationActualPriceAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationActualPrice::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()
                ->addSuccess('Repricing Synchronization Actual Price was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Synchronization Actual Price was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Repricing Inspect Products"
     * @description "Process Repricing Inspect Products Task"
     */
    public function repricingRepricingInspectProductsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingInspectProducts::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Repricing Inspect Products was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Repricing Inspect Products was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Health Status"
     * @description "Process Health Status"
     */
    public function healthStatusNotificationsAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\HealthStatus::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Health Status was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Health Status was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    /**
     * @title "Process Archive Orders Entities"
     * @description "Process Archive Orders Entities Task"
     */
    public function archiveOrdersEntitiesAction()
    {
        $cronRunner = $this->modelFactory->getObject('Cron\Runner\Developer');
        $cronRunner->setAllowedTasks(array(
            \Ess\M2ePro\Model\Cron\Task\ArchiveOrdersEntities::NICK
        ));

        if ($cronRunner->process()) {
            $this->getMessageManager()->addSuccess('Archive Orders Entities was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Archive Orders Entities was performed with errors.');
        }

        $this->getResponse()->setBody('<pre>'.$cronRunner->getOperationHistory()->getFullDataInfo().'</pre>');
    }

    //########################################
}