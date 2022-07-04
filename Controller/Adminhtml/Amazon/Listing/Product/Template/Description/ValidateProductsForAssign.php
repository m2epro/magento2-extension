<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

use \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description;

class ValidateProductsForAssign extends Description
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

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

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

        $filteredProductsIdsByType = $this->variationHelper->filterProductsByMagentoProductType($productsIdsLocked);

        if (count($productsIdsLocked) != count($filteredProductsIdsByType)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Selected action was not completed for one or more Items. Description Policy cannot be assigned
                    to Simple with Custom Options, Bundle and Downloadable with Separated Links Magento Products.'
                )
            ];
        }

        if (empty($filteredProductsIdsByType)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);

            return $this->getResult();
        }

        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Description::class);
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
