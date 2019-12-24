<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepThreeSaveCategorySpecificsToSession
 */
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
            ['specifics' => $categorySpecificsData['specifics']]
        );

        $this->setSessionValue('specifics', $sessionSpecificsData);

        return $this->getResult();
    }

    //########################################
}
