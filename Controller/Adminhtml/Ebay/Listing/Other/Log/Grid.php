<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Listing\Other', $id, 'id', false);

        if (is_null($model)) {
            $model = $this->ebayFactory->getObject('Listing\Other');
        }

        if (!$model->getId() && $id) {
            return;
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $model->getData());

        $response = $this->createBlock('Ebay\Listing\Other\Log\Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}