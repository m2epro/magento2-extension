<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ViewLogIcon;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Model\Ebay\Account\PickupStore\Log;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ViewLogIcon\PickupStore
 */
class PickupStore extends \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing
{
    use Traits\RendererTrait;

    //########################################

    protected function getAvailableActions()
    {
        return [
            Log::ACTION_UNKNOWN        => $this->getHelper('Module\Translation')->__('Unknown'),
            Log::ACTION_ADD_PRODUCT    => $this->getHelper('Module\Translation')->__('Add'),
            Log::ACTION_UPDATE_QTY     => $this->getHelper('Module\Translation')->__('Update'),
            Log::ACTION_DELETE_PRODUCT => $this->getHelper('Module\Translation')->__('Delete'),
        ];
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $stateId = (int)$row->getData('state_id');
        $columnId = (int)$row->getData('id');
        $availableActionsId = array_keys($this->getAvailableActions());

        // Get last messages
        // ---------------------------------------
        $dbSelect = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_Log')
                    ->getResource()->getMainTable(),
                ['id', 'action_id', 'action', 'type', 'description', 'create_date']
            )
            ->where('`account_pickup_store_state_id` = ?', $stateId)
            ->where('`action_id` IS NOT NULL')
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $this->resourceConnection->getConnection()->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        foreach ($logs as &$log) {
            $log['initiator'] = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        $summary = $this->createBlock('Listing_Log_Grid_LastActions')->setData([
            'entity_id' => (int)$columnId,
            'logs' => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'EbayListingPickupStoreGridObj.viewItemHelp',
            'hide_help_handler' => 'EbayListingPickupStoreGridObj.hideItemHelp',
        ]);

        $pickupStoreState = $this->activeRecordFactory->getObjectLoaded('Ebay_Account_PickupStore_State', $stateId);

        $this->jsTranslator->addTranslations([
            'Log For SKU ' . $stateId =>
                $this->getHelper('Module\Translation')->__('Log For SKU (%s%)', $pickupStoreState->getSku())
        ]);

        return $summary->toHtml();
    }

    //########################################
}
