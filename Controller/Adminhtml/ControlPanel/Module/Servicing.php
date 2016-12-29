<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Servicing extends Command
{
    //########################################

    /**
     * @title "Process License"
     * @description "Process License"
     */
    public function runLicenseAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('license')) {
            $this->getMessageManager()->addSuccess('Servicing License was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing License was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Messages"
     * @description "Process Messages Task"
     */
    public function runMessagesAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('messages')) {
            $this->getMessageManager()->addSuccess('Servicing Messages was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Messages was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Settings"
     * @description "Process Settings Task"
     */
    public function runSettingsAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('settings')) {
            $this->getMessageManager()->addSuccess('Servicing Settings was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Settings was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Exceptions"
     * @description "Process Exceptions Task"
     */
    public function runExceptionsAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('exceptions')) {
            $this->getMessageManager()->addSuccess('Servicing Exceptions was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Exceptions was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Marketplaces"
     * @description "Process Marketplaces Task"
     */
    public function runMarketplacesAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('marketplaces')) {
            $this->getMessageManager()->addSuccess('Servicing Marketplaces was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Marketplaces was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Cron"
     * @description "Process Cron Task"
     */
    public function runCronAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('cron')) {
            $this->getMessageManager()->addSuccess('Servicing Cron was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Cron was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Process Statistic"
     * @description "Process Statistic Task"
     */
    public function runStatisticAction()
    {
        /** @var \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher */
        $servicingDispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
        $servicingDispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);

        if ($servicingDispatcher->processTask('statistic')) {
            $this->getMessageManager()->addSuccess('Servicing Statistic was successfully performed.');
        } else {
            $this->getMessageManager()->addError('Servicing Statistic was performed with errors.');
        }

        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################
}