<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepTwoSaveToSession extends Settings
{

    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds('products_id');
        $templateData = $this->getRequest()->getParam('template_data');
        $templateData = (array)$this->getHelper('Data')->jsonDecode($templateData);

        $listing = $this->getListing();

        $this->addCategoriesPath($templateData,$listing);

        $key = $this->getSessionDataKey();
        $sessionData = $this->getSessionValue($key);

        if ($this->getSessionValue('mode') == 'category') {

            foreach ($ids as $categoryId) {
                $sessionData[$categoryId]['listing_products_ids'] = $this->getSelectedListingProductsIdsByCategoriesIds(
                    array($categoryId)
                );
            }
        }

        foreach ($ids as $id) {
            $sessionData[$id] = array_merge($sessionData[$id], $templateData);
        }

        $this->setSessionValue($key, $sessionData);

        return $this->getResult();
    }

    //########################################
}