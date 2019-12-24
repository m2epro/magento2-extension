<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\GetSessionProductsIds
 */
class GetSessionProductsIds extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        $this->setJsonContent([
            'ids' => $selectedProductsIds
        ]);

        return $this->getResult();
    }

    //########################################
}
