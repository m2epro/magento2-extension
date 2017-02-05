<?php

namespace Ess\M2ePro\Controller\Adminhtml;

abstract class Order extends Base
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_sales') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon_sales');
    }

    protected function getProductOptionsDataFromPost()
    {
        $optionsData = $this->getRequest()->getParam('option_id');

        if (is_null($optionsData) || count($optionsData) == 0) {
            return array();
        }

        foreach ($optionsData as $optionId => $optionData) {
            $optionData = $this->getHelper('Data')->jsonDecode($optionData);

            if (!isset($optionData['value_id']) || !isset($optionData['product_ids'])) {
                return array();
            }

            $optionsData[$optionId] = $optionData;
        }

        return $optionsData;
    }
}