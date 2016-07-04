<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepTwoModeProductValidate extends Settings
{

    //########################################

    public function execute()
    {
        $key = $this->getSessionDataKey();
        $sessionData = $this->getSessionValue($key);

        $this->clearSpecificsSession();

        if (empty($sessionData)) {
            $this->setJsonContent([
                'validation' => false,
                'message' => $this->__(
                    'There are no Items to continue. Please, go back and select the Item(s).'
                )
            ]);

            return $this->getResult();
        }

        $failedCount = 0;
        foreach ($sessionData as $categoryData) {

            if ($categoryData['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $key = 'category_main_id';
            } else {
                $key = 'category_main_attribute';
            }

            if (!$categoryData[$key]) {
                $failedCount++;
            }
        }

        $this->setJsonContent([
            'validation' => $failedCount == 0,
            'total_count' => count($sessionData),
            'failed_count' => $failedCount
        ]);

        return $this->getResult();
    }

    //########################################
}