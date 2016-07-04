<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Listing\Other', $id, 'id', false);

        if (is_null($model)) {
            $model = $this->activeRecordFactory->getObject('Listing\Other');
        }

        if (!$model->getId() && $id) {
            return;
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $model->getData());

        $this->setAjaxContent($this->createBlock('Amazon\Listing\Other\Log\Grid'));

        return $this->getResult();
    }

    //########################################
}