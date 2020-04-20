<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search\SearchAsinAuto
 */
class SearchAsinAuto extends Main
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            return $this->getResponse()->setBody('You should select one or more Products');
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $productsToSearch = [];
        foreach ($productsIds as $productId) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

            $searchStatusInProgress = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS;
            if ($listingProduct->isNotListed() &&
                !$listingProduct->getChildObject()->getData('general_id') &&
                !$listingProduct->getChildObject()->getData('is_general_id_owner') &&
                $listingProduct->getChildObject()->getData('search_settings_status') != $searchStatusInProgress
            ) {
                $productsToSearch[] = $listingProduct;
            }
        }

        if (!empty($productsToSearch)) {
            /** @var $dispatcher \Ess\M2ePro\Model\Amazon\Search\Dispatcher */
            $dispatcher = $this->modelFactory->getObject('Amazon_Search_Dispatcher');
            $result = $dispatcher->runSettings($productsToSearch);

            if ($result === false) {
                return $this->getResponse()->setBody('1');
            }
        }

        $this->setAjaxContent('0', false);

        return $this->getResult();
    }
}
