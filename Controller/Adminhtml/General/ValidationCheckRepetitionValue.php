<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class ValidationCheckRepetitionValue extends General
{
    public function execute()
    {
        $model = $this->getRequest()->getParam('model','');

        $component = $this->getRequest()->getParam('component');

        $dataField = $this->getRequest()->getParam('data_field','');
        $dataValue = $this->getRequest()->getParam('data_value','');

        if ($model == '' || $dataField == '' || $dataValue == '') {
            $this->setJsonContent(['result'=>false]);
            return $this->getResult();
        }

        $collection = $this->activeRecordFactory->getObject($model)->getCollection();

        if ($dataField != '' && $dataValue != '') {
            $collection->addFieldToFilter($dataField, array('in'=>array($dataValue)));
        }

        $idField = $this->getRequest()->getParam('id_field','id');
        $idValue = $this->getRequest()->getParam('id_value','');

        if ($idField != '' && $idValue != '') {
            $collection->addFieldToFilter($idField, array('nin'=>array($idValue)));
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