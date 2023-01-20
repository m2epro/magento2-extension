<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SearchAsinAuto extends Main
{
    /** @var \Ess\M2ePro\Model\Amazon\Search\Dispatcher */
    private $searchDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Search\Dispatcher $searchDispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->searchDispatcher = $searchDispatcher;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

            $searchStatusInProgress = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS;
            if (
                $listingProduct->isNotListed()
                && !$listingProduct->getChildObject()->getData('general_id')
                && !$listingProduct->getChildObject()->getData('is_general_id_owner')
                && $listingProduct->getChildObject()->getData('search_settings_status') != $searchStatusInProgress
            ) {
                $productsToSearch[] = $listingProduct;
            }
        }

        if (!empty($productsToSearch)) {
            $result = $this->searchDispatcher->runSettings($productsToSearch);
            if ($result === false) {
                return $this->getResponse()->setBody('1');
            }
        }

        $this->setAjaxContent('0', false);

        return $this->getResult();
    }
}
