<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\ModelGetAll
 */
class ModelGetAll extends General
{
    //########################################

    public function execute()
    {
        $model = $this->getRequest()->getParam('model', '');
        $componentMode = $this->getRequest()->getParam('component_mode', '');

        $idField = $this->getRequest()->getParam('id_field', 'id');
        $dataField = $this->getRequest()->getParam('data_field', '');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');

        if ($model == '' || $idField == '' || $dataField == '') {
            return $this->setJsonContent([]);
        }

        $model = str_replace('_', '\\', $model);

        if ($componentMode != '') {
            $collection = $this->parentFactory->getObject($componentMode, $model)->getCollection();
        } else {
            $collection = $this->activeRecordFactory->getObject($model)->getCollection();
        }

        $marketplaceId != '' && $collection->addFieldToFilter('marketplace_id', $marketplaceId);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns([$idField, $dataField]);

        $sortField = $this->getRequest()->getParam('sort_field', '');
        $sortDir = $this->getRequest()->getParam('sort_dir', 'ASC');

        if ($sortField != '' && $sortDir != '') {
            $collection->setOrder('main_table.'.$sortField, $sortDir);
        }

        $limit = $this->getRequest()->getParam('limit', null);
        $limit !== null && $collection->setPageSize((int)$limit);

        $data = $collection->toArray();

        $this->setJsonContent($data['items']);
        return $this->getResult();
    }

    //########################################
}
