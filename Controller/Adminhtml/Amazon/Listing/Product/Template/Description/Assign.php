<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation */
    protected $variationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($transactionFactory, $amazonFactory, $context);
        $this->variationHelper = $variationHelper;
    }

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

        $msgType = 'success';
        $messages = [];

        $productsIdsTemp = $this->filterProductsForMapOrUnmapDescriptionTemplate($productsIds);

        if (count($productsIdsTemp) != count($productsIds)) {
            $msgType = 'warning';
            $messages[] = $this->__(
                'Description Policy cannot be assigned because %count% Item(s) are Ready or in Process
                of New ASIN(s)/ISBN(s) creation.',
                count($productsIds) - count($productsIdsTemp)
            );
        }

        $filteredProductsIdsByType = $this->variationHelper->filterProductsByMagentoProductType($productsIdsTemp);

        if (count($productsIdsTemp) != count($filteredProductsIdsByType)) {
            $msgType = 'warning';
            $messages[] = $this->__(
                'Description Policy cannot be assigned because %count% Items are Simple
                 with Custom Options or Bundle Magento Products.',
                count($productsIdsTemp) - count($filteredProductsIdsByType)
            );
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

        $messages[] = $this->__(
            'Description Policy was assigned to %count% Products',
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
