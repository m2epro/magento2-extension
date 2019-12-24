<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\RemoveCustomItem
 */
class RemoveCustomItem extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $helper = $this->getHelper('Component_Ebay_Motors');
        $motorsType = $this->getRequest()->getParam('motors_type');
        $keyId = $this->getRequest()->getParam('key_id');

        if (!$motorsType || !$keyId) {
            $this->setJsonContent([
                'result'  => false,
                'message' => $this->__('The some of required fields are not filled up.')
            ]);

            return $this->getResult();
        }

        $tableName = $helper->getDictionaryTable($motorsType);
        $idKey = $helper->getIdentifierKey($motorsType);

        $connection = $this->resourceConnection->getConnection();
        $conditions = ["{$idKey} = ?" => $keyId];
        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $conditions['scope = ?'] = $helper->getEpidsScopeByType($motorsType);
        }

        $connection->delete($tableName, $conditions);

        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_motor_group');

        $select = $connection->select();
        $select->from(['emg' => $table], ['id'])
               ->where('items_data REGEXP ?', '"ITEM"\|"'.$keyId.'"');

        $groupIds = $connection->fetchCol($select);

        foreach ($groupIds as $groupId) {
            /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
            $group = $this->activeRecordFactory->getObjectLoaded('Ebay_Motor_Group', $groupId);

            $items = $group->getItems();
            unset($items[$keyId]);

            if (!empty($items)) {
                $group->setItemsData($helper->buildItemsAttributeValue($items));
                $group->save();
            } else {
                $group->delete();
            }
        }

        $this->setJsonContent([
            'result' => true
        ]);

        return $this->getResult();
    }

    //########################################
}
