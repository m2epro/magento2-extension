<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class SaveAsGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $post = (array)$this->getRequest()->getPost();

        $data = array(
            'title' => $post['title'],
            'type' => $post['type'],
            'mode' => $post['mode'],
        );

        if ($data['mode'] == \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_ITEM) {

            parse_str($post['items'], $post['items']);

            $itemsData = array();
            foreach ($post['items'] as $id => $note) {
                $itemsData[] = array(
                    'id' => $id,
                    'note' => $note
                );
            }

            $data['items_data'] = $this->getHelper('Component\Ebay\Motors')->buildItemsAttributeValue(
                $itemsData
            );
        }

        $model = $this->activeRecordFactory->getObject('Ebay\Motor\Group');
        $model->addData($data)->save();

        if ($data['mode'] == \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_FILTER) {

            $filtersIds = $post['items'];
            if (!is_array($filtersIds)) {
                $filtersIds = explode(',', $filtersIds);
            }

            $tableName = $this->resourceConnection->getTableName('m2epro_ebay_motor_filter_to_group');
            $connection = $this->resourceConnection->getConnection();

            foreach ($filtersIds as $filterId) {
                $connection->insert($tableName, array(
                        'filter_id' => $filterId,
                        'group_id' => $model->getId(),
                    )
                );
            }
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}