<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search\SearchAsinManual
 */
class SearchAsinManual extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $query = trim($this->getRequest()->getParam('query'));

        if (empty($productId)) {
            return $this->getResponse()->setBody('No product_id!');
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        if ($listingProduct->isNotListed() &&
            !$listingProduct->getChildObject()->getData('is_general_id_owner') &&
            !$listingProduct->getChildObject()->getData('general_id')
        ) {
            $marketplaceObj = $listingProduct->getListing()->getMarketplace();

            /** @var $dispatcher \Ess\M2ePro\Model\Amazon\Search\Dispatcher */
            $dispatcher = $this->modelFactory->getObject('Amazon_Search_Dispatcher');
            $result = $dispatcher->runCustom($listingProduct, $query);

            $message = $this->__('Server is currently unavailable. Please try again later.');
            if ($result === false || $result['data'] === false) {
                $response = ['result' => 'error','text' => $message];
                $this->setJsonContent($response);

                return $this->getResult();
            }

            $this->getHelper('Data\GlobalData')->setValue('search_data', $result);
            $this->getHelper('Data\GlobalData')->setValue('product_id', $productId);
            $this->getHelper('Data\GlobalData')->setValue('marketplace_id', $marketplaceObj->getId());
        } else {
            $this->getHelper('Data\GlobalData')->setValue('search_data', []);
        }

        $response = [
            'result' => 'success',
            'html'   => $this->createBlock('Amazon_Listing_Product_Search_Grid')->toHtml()
        ];

        $this->setJsonContent($response);

        return $this->getResult();
    }
}
