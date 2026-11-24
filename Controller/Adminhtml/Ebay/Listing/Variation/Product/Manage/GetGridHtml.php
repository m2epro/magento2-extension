<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage;

class GetGridHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->listingProductRepository = $listingProductRepository;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $view = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs\Variations\Grid::class,
                '',
                [
                    'listingProduct' => $this->listingProductRepository->get((int)$productId),
                ]
            );

        $this->setAjaxContent($view);

        return $this->getResult();
    }
}
