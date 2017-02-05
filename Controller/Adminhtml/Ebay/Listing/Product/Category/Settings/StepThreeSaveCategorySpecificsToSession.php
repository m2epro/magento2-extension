<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepThreeSaveCategorySpecificsToSession extends Settings
{

    //########################################

    public function execute()
    {
        $category = $this->getRequest()->getParam('category');
        $categorySpecificsData = $this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('data'));

        $sessionSpecificsData = $this->getSessionValue('specifics');

        $sessionSpecificsData[$category] = array_merge(
            $sessionSpecificsData[$category],
            array('specifics' => $categorySpecificsData['specifics'])
        );

        $this->setSessionValue('specifics', $sessionSpecificsData);

        return $this->getResult();
    }

    //########################################
}