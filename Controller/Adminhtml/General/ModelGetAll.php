<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class ModelGetAll extends General
{
    //########################################

    public function execute()
    {
        $model = $this->getRequest()->getParam('model','');
        $componentMode = $this->getRequest()->getParam('component_mode', '');

        $idField = $this->getRequest()->getParam('id_field','id');
        $dataField = $this->getRequest()->getParam('data_field','');

        if ($model == '' || $idField == '' || $dataField == '') {
            return $this->setJsonContent([]);
        }

        $model = str_replace('_', '\\', $model);

        $collection = $this->activeRecordFactory->getObject($model)->getCollection();
        $componentMode != '' && $collection->addFieldToFilter('component_mode', $componentMode);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array($idField, $dataField));

        $sortField = $this->getRequest()->getParam('sort_field','');
        $sortDir = $this->getRequest()->getParam('sort_dir','ASC');

        if ($sortField != '' && $sortDir != '') {
            $collection->setOrder('main_table.'.$sortField,$sortDir);
        }

        $limit = $this->getRequest()->getParam('limit',NULL);
        !is_null($limit) && $collection->setPageSize((int)$limit);

        $data = $collection->toArray();

        $this->setJsonContent($data['items']);
        return $this->getResult();
    }

    //########################################
}