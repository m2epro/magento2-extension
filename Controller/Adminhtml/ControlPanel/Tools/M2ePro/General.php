<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro\General
 */
class General extends Command
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Manager Manager */
    private $inspectionManager;

    //########################################

    public function __construct(Manager $inspectionManager, Context $context)
    {
        $this->inspectionManager = $inspectionManager;

        parent::__construct($context);
    }

    //########################################

    /**
     * @hidden
     */
    public function deleteBrokenDataAction()
    {
        $tableNames = $this->getRequest()->getParam('table', []);

        if (empty($tableNames)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables $inspection */
        $inspection = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables::class);
        $inspection->fix($tableNames);
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
        $info = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables::class)
            ->getBrokenRecordsInfo($tableName);

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
            $this->_redirect($this->_redirect->getRefererUrl());
        }

        $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\RemovedStores::class)
            ->fix([$replaceIdFrom => $replaceIdTo]);

        $this->_redirect($this->_redirect->getRefererUrl());
    }

    // ---------------------------------------

    /**
     * @hidden
     */
    public function repairListingProductStructureAction()
    {
        $repairInfo = $this->getRequest()->getPost('repair_info');

        if (empty($repairInfo)) {
            $this->_redirect($this->_redirect->getRefererUrl());
        }

        $dataForRepair = [];
        foreach ($repairInfo as $item) {
            $temp = (array)$this->getHelper('Data')->jsonDecode($item);
            $dataForRepair[$temp['table']] = $temp['ids'];
        }

        $inspector = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ListingProductStructure::class);
        $inspector->fix($dataForRepair);

        $this->_redirect($this->_redirect->getRefererUrl());
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

        $inspector = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\OrderItemStructure::class);
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

        $inspector = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\EbayItemIdStructure::class);
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

        $inspector = $this->inspectionManager
            ->getInspection(\Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\AmazonProductWithoutVariations::class);
        $inspector->fix($dataForRepair);
    }

    //########################################
}
