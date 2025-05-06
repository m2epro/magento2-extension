<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class ViewGrid extends Main
{
    private \Ess\M2ePro\Model\Walmart\Listing\Product\Repository $listingProductRepository;
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType\GridFactory $gridFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\Product\Repository $listingProductRepository,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType\GridFactory $gridFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->listingProductRepository = $listingProductRepository;
        $this->gridFactory = $gridFactory;
    }

    public function execute()
    {
        $listingProductsIds = $this->getRequest()->getParam('products_ids');
        if (!is_array($listingProductsIds)) {
            $listingProductsIds = array_filter(explode(',', $listingProductsIds));
        }

        if (empty($listingProductsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $mapToTemplateJsFn = $this->getRequest()->getParam('map_to_template_js_fn');
        $createNewTemplateJsFn = $this->getRequest()->getParam('create_new_template_js_fn');

        $marketplaceId = $this->listingProductRepository->findFirstByIds($listingProductsIds)
                                                        ->getListing()
                                                        ->getMarketplaceId();
        $grid = $this->gridFactory->create(
            $marketplaceId,
            $listingProductsIds,
            $mapToTemplateJsFn,
            $createNewTemplateJsFn,
            $this->getLayout()
        );

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
