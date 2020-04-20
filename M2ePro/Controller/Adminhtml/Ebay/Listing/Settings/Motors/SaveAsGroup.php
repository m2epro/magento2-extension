<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\SaveAsGroup
 */
class SaveAsGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $post = (array)$this->getRequest()->getPost();

        $data = [
            'title' => $post['title'],
            'type' => $post['type'],
            'mode' => $post['mode'],
        ];

        if ($data['mode'] == \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_ITEM) {
            parse_str($post['items'], $post['items']);

            $itemsData = [];
            foreach ($post['items'] as $id => $note) {
                $itemsData[] = [
                    'id' => $id,
                    'note' => $note
                ];
            }

            $data['items_data'] = $this->getHelper('Component_Ebay_Motors')->buildItemsAttributeValue(
                $itemsData
            );
        }

        $model = $this->activeRecordFactory->getObject('Ebay_Motor_Group');
        $model->addData($data)->save();

        if ($data['mode'] == \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_FILTER) {
            $filtersIds = $post['items'];
            if (!is_array($filtersIds)) {
                $filtersIds = explode(',', $filtersIds);
            }

            $tableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');
            $connection = $this->resourceConnection->getConnection();

            foreach ($filtersIds as $filterId) {
                $connection->insert($tableName, [
                        'filter_id' => $filterId,
                        'group_id' => $model->getId(),
                    ]);
            }
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
