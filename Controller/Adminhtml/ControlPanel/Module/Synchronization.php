<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Synchronization
 */
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
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS,
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::POLICIES
        ]);
    }

    //########################################

    /**
     * @title "Global - Processing"
     * @description "Run only processing synchronization as developer mode"
     * @confirm "Are you sure?"
     */
    public function synchGlobalProcessingAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::PROCESSING
        ]);
    }

    /**
     * @title "Global - Magento Products"
     * @description "Run only global magento products synchronization as developer mode"
     * @confirm "Are you sure?"
     */
    public function synchGlobalMagentoProductsAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS
        ]);
    }

    /**
     * @title "Global - Queue"
     * @description "Run only queue synchronization as developer mode"
     * @confirm "Are you sure?"
     * @new_line
     */
    public function synchGlobalQueueAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE
        ]);
    }

    //########################################

    /**
     * @title "Component - General"
     * @description "Run only general synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function generalAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::GENERAL
        ]);
    }

    /**
     * @title "Component - Listings Products"
     * @description "Run only listings products synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchListingsProductsAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS
        ]);
    }

    /**
     * @title "Component - Other Listings"
     * @description "Run only Other listings synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchOtherListingsAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS
        ]);
    }

    /**
     * @title "Component - Templates"
     * @description "Run only stock level synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchTemplatesAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES
        ]);
    }

    /**
     * @title "Component - Orders"
     * @description "Run only orders synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchOrdersAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS
        ]);
    }

    /**
     * @title "Component - Marketplaces"
     * @description "Run only marketplaces synchronization as developer mode"
     * @prompt "Please enter Marketplace ID."
     * @prompt_var "marketplace_id"
     * @components
     */
    public function synchMarketplacesAction()
    {
        $params = [];

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        if (!empty($marketplaceId)) {
            $params['marketplace_id'] = $marketplaceId;
        }

        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES
        ], $params);
    }

    /**
     * @title "Component - Policies"
     * @description "Run only policies synchronization as developer mode"
     * @confirm "Are you sure?"
     * @components
     */
    public function synchPoliciesAction()
    {
        $this->processSynchTasks([
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::POLICIES
        ]);
    }

    //########################################

    private function processSynchTasks($tasks, $params = [])
    {
        session_write_close();

        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $components = $this->getHelper('Component')->getComponents();
        if ($this->getRequest()->getParam('component')) {
            $components = [$this->getRequest()->getParam('component')];
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
