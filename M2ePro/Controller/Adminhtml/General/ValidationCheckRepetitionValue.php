<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\ValidationCheckRepetitionValue
 */
class ValidationCheckRepetitionValue extends General
{
    public function execute()
    {
        $model = $this->getRequest()->getParam('model', '');

        $component = $this->getRequest()->getParam('component');

        $dataField = $this->getRequest()->getParam('data_field', '');
        $dataValue = $this->getRequest()->getParam('data_value', '');

        if ($model == '' || $dataField == '' || $dataValue == '') {
            $this->setJsonContent(['result'=>false]);
            return $this->getResult();
        }

        $collection = $this->activeRecordFactory->getObject($model)->getCollection();

        if ($dataField != '' && $dataValue != '') {
            $collection->addFieldToFilter($dataField, ['in'=>[$dataValue]]);
        }

        $idField = $this->getRequest()->getParam('id_field', 'id');
        $idValue = $this->getRequest()->getParam('id_value', '');

        if ($idField != '' && $idValue != '') {
            $collection->addFieldToFilter($idField, ['nin'=>[$idValue]]);
        }

        if ($component) {
            $collection->addFieldToFilter('component_mode', $component);
        }

        $filterField = $this->getRequest()->getParam('filter_field');
        $filterValue = $this->getRequest()->getParam('filter_value');

        if ($filterField && $filterValue) {
            $collection->addFieldToFilter($filterField, $filterValue);
        }

        $this->setJsonContent(['result'=>!(bool)$collection->getSize()]);
        return $this->getResult();
    }
}
