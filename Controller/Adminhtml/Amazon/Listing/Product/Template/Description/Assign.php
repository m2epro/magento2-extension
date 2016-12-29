<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $templateId = $this->getRequest()->getParam('template_id');

        if (empty($productsIds) || empty($templateId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper */
        $variationHelper = $this->getHelper('Component\Amazon\Variation');

        $msgType = 'success';
        $messages = array();

        $productsIdsTemp = $this->filterProductsForMapOrUnmapDescriptionTemplate($productsIds);

        if (count($productsIdsTemp) != count($productsIds)) {
            $msgType = 'warning';
            $messages[] = $this->__(
                'Description Policy cannot be assigned because %count% Item(s) are Ready or in Process
                of New ASIN(s)/ISBN(s) creation.', count($productsIds) - count($productsIdsTemp)
            );
        }

        $filteredProductsIdsByType = $variationHelper->filterProductsByMagentoProductType($productsIdsTemp);

        if (count($productsIdsTemp) != count($filteredProductsIdsByType)) {
            $msgType = 'warning';
            $messages[] = $this->__(
                'Description Policy cannot be assigned because %count% Items are Simple
                 with Custom Options or Bundle Magento Products.',
                count($productsIdsTemp) - count($filteredProductsIdsByType));
        }

        if (empty($filteredProductsIdsByType)) {
            $this->setJsonContent([
                'type' => $msgType,
                'messages' => $messages
            ]);

            return $this->getResult();
        }

        $this->setDescriptionTemplateForProducts($filteredProductsIdsByType, $templateId);
        $this->runProcessorForParents($filteredProductsIdsByType);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $template */
        $template = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\Description', $templateId);
        $template->setSynchStatusNeed($template->getDataSnapshot(),array());

        $messages[] = $this->__(
            'Description Policy was successfully assigned to %count% Products',
            count($filteredProductsIdsByType)
        );

        $this->setJsonContent([
            'type' => $msgType,
            'messages' => $messages,
            'products_ids' => implode(',', $filteredProductsIdsByType)
        ]);

        return $this->getResult();
    }
}