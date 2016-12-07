<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

use \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

class ValidateProductsForAssign extends Description
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $variationHelper = $this->getHelper('Component\Amazon\Variation');

        $messages = [];

        $productsIdsTemp = $this->filterProductsForMapOrUnmapDescriptionTemplate($productsIds);

        if (count($productsIdsTemp) != count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Description Policy was not assigned because the Products are in the process
                     of new ASIN(s)/ISBN(s) creation'
                )
            ];
        }

        $productsIdsLocked = $this->filterLockedProducts($productsIdsTemp);

        if (count($productsIdsTemp) != count($productsIdsLocked)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Description Policy cannot be assigned because the Products are in Action.'
                )
            ];
        }

        $filteredProductsIdsByType = $variationHelper->filterProductsByMagentoProductType($productsIdsLocked);

        if (count($productsIdsLocked) != count($filteredProductsIdsByType)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Description Policy was not assigned because the Items are Simple
                    With Custom Options or Bundle Magento Products.'
                )
            ];
        }

        if (empty($filteredProductsIdsByType)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);

            return $this->getResult();
        }

        $block = $this->createBlock('Amazon\Listing\Product\Template\Description');
        if (!empty($messages)) {
            $block->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $block->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $filteredProductsIdsByType)
        ]);

        return $this->getResult();
    }
}