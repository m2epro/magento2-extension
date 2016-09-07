<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepTwoModeCategoryValidate extends Settings
{

    //########################################

    public function execute()
    {
        $key = $this->getSessionDataKey();
        $sessionData = $this->getSessionValue($key);

        $this->clearSpecificsSession();

        if (empty($sessionData)) {
            return $this->setJsonContent([
                'validation' => false,
                'message' => $this->__(
                    'Magento Categories are not specified on Products you are adding.'
                )
            ]);
        }

        $isValid = true;
        foreach ($sessionData as $categoryData) {

            if ($categoryData['category_main_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $key = 'category_main_id';
            } else {
                $key = 'category_main_attribute';
            }

            if (!$categoryData[$key]) {
                $isValid = false;
            }
        }

        $this->setJsonContent([
            'validation' => $isValid,
            'message' => $this->__(
                'You have not selected the eBay Catalog Primary Category for some Magento Categories.'
            )
        ]);

        return $this->getResult();
    }

    //########################################
}