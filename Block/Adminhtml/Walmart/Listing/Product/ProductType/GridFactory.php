<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType;

class GridFactory
{
    public function create(
        int $marketplaceId,
        array $productsIds,
        ?string $mapToTemplateJsFn,
        ?string $createNewTemplateJsFn,
        \Magento\Framework\View\LayoutInterface $layout
    ): Grid {
        /** @var Grid $block */
        $block = $layout->createBlock(Grid::class, '', [
           'marketplaceId' => $marketplaceId,
           'productsIds' => $productsIds
        ]);

        if ($createNewTemplateJsFn !== null) {
            $block->setCreateNewTemplateJsFn($createNewTemplateJsFn);
        }

        if ($mapToTemplateJsFn !== null) {
            $block->setMapToTemplateJsFn($mapToTemplateJsFn);
        }

        return $block;
    }
}
