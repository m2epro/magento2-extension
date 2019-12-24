<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\SetNoteToFilters
 */
class SetNoteToFilters extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $filtersIds = $this->getRequest()->getParam('filters_ids');
        $note = $this->getRequest()->getParam('note');

        if (!is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        $tableName = $this->activeRecordFactory->getObject('Ebay_Motor_Filter')->getResource()->getMainTable();

        $connection = $this->resourceConnection->getConnection();
        $connection->update($tableName, [
            'note' => $note
        ], '`id` IN ('.implode(',', $filtersIds).')');

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
