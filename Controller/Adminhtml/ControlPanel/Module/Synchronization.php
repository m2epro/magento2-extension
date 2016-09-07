<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Synchronization extends Command
{
    //########################################

    /**
     * @title "Run All"
     * @description "Run all cron synchronization tasks as developer mode"
     * @confirm "Are you sure?"
     * @components
     * @new_line
     */
    public function synchCronTasksAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::POLICIES
        ));
    }

    //########################################

    /**
     * @title "General"
     * @description "Run only general synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function generalAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL
        ));
    }

    /**
     * @title "Processing"
     * @description "Run only defaults synchronization as developer mode"
     * @confirm "Are you sure?"
     */
    public function synchProcessingAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING
        ));
    }

    //########################################

    /**
     * @title "Listings Products"
     * @description "Run only listings products synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchListingsProductsAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS
        ));
    }

    /**
     * @title "Other Listings"
     * @description "Run only Other listings synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchOtherListingsAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS
        ));
    }

    //########################################

    /**
     * @title "Templates"
     * @description "Run only stock level synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchTemplatesAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES
        ));
    }

    //########################################

    /**
     * @title "Marketplaces"
     * @description "Run only marketplaces synchronization as developer mode"
     * @prompt "Please enter Marketplace ID."
     * @prompt_var "marketplace_id"
     * @components
     */
    public function synchMarketplacesAction()
    {
        $params = array();

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        if (!empty($marketplaceId)) {
            $params['marketplace_id'] = $marketplaceId;
        }

        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES
        ), $params);
    }

    //########################################

    /**
     * @title "Orders"
     * @description "Run only orders synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchOrdersAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS
        ));
    }

    //########################################

    /**
     * @title "Magento Products"
     * @description "Run only magento products synchronization as developer mode"
     * @confirm "Are you sure?"
     */
    public function synchMagentoProductsAction()
    {
        $this->processSynchTasks(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS
        ));
    }

    //########################################

    private function processSynchTasks($tasks, $params = array())
    {
        session_write_close();

        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $components = $this->getHelper('Component')->getComponents();
        if ($this->getRequest()->getParam('component')) {
            $components = array($this->getRequest()->getParam('component'));
        }

        $dispatcher->setAllowedComponents($components);
        $dispatcher->setAllowedTasksTypes($tasks);

        $dispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER);
        $dispatcher->setParams($params);

        $dispatcher->process();

        $this->getResponse()->setBody('<pre>'.$dispatcher->getOperationHistory()->getFullProfilerInfo().'</pre>');
    }

    //########################################
}