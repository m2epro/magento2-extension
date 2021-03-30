<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Template;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Template\SetDefaultValuesInSyncPolicy
 */
class SetDefaultValuesInSyncPolicy extends Base
{
    //########################################

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $templateTable = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix("m2epro_{$component}_template_synchronization");
            $templates = $connection
                ->select()
                ->from($templateTable, 'template_synchronization_id')
                ->where('relist_qty_calculated = ?', \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE)
                ->orWhere('stop_qty_calculated = ?', \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE)
                ->query();

            while ($template = $templates->fetch()) {
                $connection->update($templateTable, [
                    'relist_qty_calculated'       => \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_YES,
                    'relist_qty_calculated_value' => '1', // Model/%component%/Template/Synchronization/Builder.php
                    'stop_qty_calculated'         => \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_YES,
                    'stop_qty_calculated_value'   => '0' // Model/%component%/Template/Synchronization/Builder.php
                ], ['template_synchronization_id = ?' => $template['template_synchronization_id']]);
            }
        }

        $messages = $this->getHelper('Module')->getUpgradeMessages();
        unset($messages['default_values_in_sync_policy']);

        $this->getHelper('Module')->getRegistry()->setValue('/upgrade/messages/', $messages);

        $this->getMessageManager()->addSuccess($this->__(
            'Relist and Stop Rules in Synchronization Policies were updated.'
        ));

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    //########################################
}
