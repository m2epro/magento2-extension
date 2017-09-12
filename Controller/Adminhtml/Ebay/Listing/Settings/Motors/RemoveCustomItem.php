<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class RemoveCustomItem extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $helper = $this->getHelper('Component\Ebay\Motors');
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
        $conditions = array("{$idKey} = ?" => $keyId);
        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $conditions['scope = ?'] = $helper->getEpidsScopeByType($motorsType);
        }

        $connection->delete($tableName, $conditions);

        $table = $this->resourceConnection->getTableName('m2epro_ebay_motor_group');

        $select = $connection->select();
        $select->from(array('emg' => $table), array('id'))
               ->where('items_data REGEXP ?', '"ITEM"\|"'.$keyId.'"');

        $groupIds = $connection->fetchCol($select);

        foreach ($groupIds as $groupId) {
            /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
            $group = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId);

            $items = $group->getItems();
            unset($items[$keyId]);

            if (count($items) > 0) {
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