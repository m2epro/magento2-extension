<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class General extends Command
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Repository */
    protected  $repository;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory */
    protected $handlerFactory;

    public function __construct(
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        Context $context,
        \Ess\M2ePro\Model\ControlPanel\Inspection\Repository $repository,
        \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory $handlerFactory
    ) {
        parent::__construct($controlPanelHelper, $context);
        $this->repository = $repository;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @hidden
     */
    public function deleteBrokenDataAction()
    {
        $tableNames = $this->getRequest()->getParam('table', []);

        if (empty($tableNames)) {
            return;
        }

        $definition = $this->repository->getDefinition('BrokenTables');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix($tableNames);
    }

    /**
     * @title "Show Broken Table IDs"
     * @hidden
     */
    public function showBrokenTableIdsAction()
    {
        $tableNames = $this->getRequest()->getParam('table', []);

        if (empty($tableNames)) {
            return $this->_redirect($this->getUrl('*/*/*', ['action' => 'checkTables']));
        }

        $tableName = array_pop($tableNames);

        $definition = $this->repository->getDefinition('BrokenTables');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $info = $inspector->getBrokenRecordsInfo($tableName);

        return '<pre>' .
               "<span>Broken Records '{$tableName}'<span><br>" .
               print_r($info, true);
    }

    /**
     * @title "Repair Removed Store"
     * @hidden
     */
    public function repairRemovedMagentoStoreAction()
    {
        $replaceIdFrom = $this->getRequest()->getParam('replace_from');
        $replaceIdTo   = $this->getRequest()->getParam('replace_to');

        if (!$replaceIdFrom || !$replaceIdTo) {
            $this->messageManager->addError('Required params are not presented.');
            $this->_redirect($this->redirect->getRefererUrl());
        }

        $definition = $this->repository->getDefinition('RemovedStores');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\RemovedStores $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix([$replaceIdFrom => $replaceIdTo]);

        $this->_redirect($this->redirect->getRefererUrl());
    }

    // ---------------------------------------

    /**
     * @hidden
     */
    public function repairListingProductStructureAction()
    {
        $repairInfo = $this->getRequest()->getPost('repair_info');

        if (empty($repairInfo)) {
            $this->_redirect($this->redirect->getRefererUrl());
        }

        $dataForRepair = [];
        foreach ($repairInfo as $item) {
            $temp = (array)$this->getHelper('Data')->jsonDecode($item);
            $dataForRepair[$temp['table']] = $temp['ids'];
        }

        $definition = $this->repository->getDefinition('ListingProductStructure');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ListingProductStructure $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix($dataForRepair);

        $this->_redirect($this->redirect->getRefererUrl());
    }

    /**
     * @hidden
     */
    public function repairOrderItemOrderStructureAction()
    {
        $repairInfo = $this->getRequest()->getPost('repair_info');

        if (empty($repairInfo)) {
            return;
        }

        $dataForRepair = (array)$this->getHelper('Data')->jsonDecode($repairInfo);

        $definition = $this->repository->getDefinition('OrderItemStructure');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\OrderItemStructure $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix($dataForRepair);
    }

    /**
     * @hidden
     */
    public function repairEbayItemIdStructureAction()
    {
        $ids = $this->getRequest()->getPost('repair_info');

        if (empty($ids)) {
            return;
        }

        $dataForRepair = (array)$this->getHelper('Data')->jsonDecode($ids);

        $definition = $this->repository->getDefinition('EbayItemIdStructure');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\EbayItemIdStructure $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix($dataForRepair);
    }

    /**
     * @hidden
     */
    public function repairAmazonProductWithoutVariationsAction()
    {
        $ids = $this->getRequest()->getPost('repair_info');

        if (empty($ids)) {
            return;
        }

        $dataForRepair = (array)$this->getHelper('Data')->jsonDecode($ids);

        $definition = $this->repository->getDefinition('AmazonProductWithoutVariations');

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\AmazonProductWithoutVariations $inspector */
        $inspector = $this->handlerFactory->create($definition);

        $inspector->fix($dataForRepair);
    }

    //########################################
}
