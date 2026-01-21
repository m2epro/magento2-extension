<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Unmanaged;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Helper\Component\Ebay;

class CreateProductAndMap extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreateService $productCreateService;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        Context $context,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreateService $productCreateService,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->productCreateService = $productCreateService;
        $this->exceptionHelper = $exceptionHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            $this->setAjaxContent('You should select one or more Products', false);

            return $this->getResult();
        }

        $productIds = explode(',', $productIds);

        $failCount = 0;
        $failMessages = [];
        foreach ($productIds as $productId) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->parentFactory
                ->getObjectLoaded(Ebay::NICK, 'Listing\Other', $productId);
            if ($listingOther->getProductId()) {
                continue;
            }
            try {
                $magentoProduct = $this->productCreateService->execute($listingOther);
            } catch (\Throwable $exception) {
                $this->exceptionHelper->process($exception);
                $failCount++;
                $failMessages[] = sprintf(
                    'Product creation fail. Unmanaged product id: %s; Fail reason: %s',
                    $productId,
                    $exception->getMessage()
                );

                continue;
            }
            $listingOther->mapProduct($magentoProduct->getId());
        }

        $this->setJsonContent([
            'fail_count' => $failCount,
            'fail_messages' => $failMessages,
        ]);

        return $this->getResult();
    }
}
