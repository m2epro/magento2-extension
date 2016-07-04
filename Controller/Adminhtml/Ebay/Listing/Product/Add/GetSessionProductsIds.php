<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class GetSessionProductsIds extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute() {
        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        $this->setJsonContent([
            'ids' => $selectedProductsIds
        ]);

        return $this->getResult();
    }

}