<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order
 */
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

        if ($optionsData === null || count($optionsData) == 0) {
            return [];
        }

        foreach ($optionsData as $optionId => $optionData) {
            $optionData = $this->getHelper('Data')->jsonDecode($optionData);

            if (!isset($optionData['value_id']) || !isset($optionData['product_ids'])) {
                return [];
            }

            $optionsData[$optionId] = $optionData;
        }

        return $optionsData;
    }
}
