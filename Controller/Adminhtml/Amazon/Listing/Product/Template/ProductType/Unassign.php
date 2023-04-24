<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType
{
    /**
     * @inheritdoc
     */
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

        $productsIdsTemp = $this->filterProductsForAssignOrUnassign($productsIds);
        if (count($productsIdsTemp) === 0) {
            $messages[] = [
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                    'Product Type cannot be unassigned from some Products because they are
                     participating in the new ASIN(s)/ISBN(s) creation.'
                ) . '</p>',
            ];
        } else {
            $productsIdsLocked = $this->filterLockedProducts($productsIdsTemp);
            if (count($productsIdsLocked) < count($productsIds)) {
                $messages[] = [
                    'type' => 'warning',
                    'text' => '<p>' . $this->__(
                        'Product Type cannot be unassigned because the Products are in Action or
                         in the process of new ASIN(s)/ISBN(s) Creation.'
                    ) . '</p>',
                ];
            }
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = [
                'type' => 'success',
                'text' => $this->__('Product Type was unassigned.'),
            ];

            $this->setProductTypeForProducts($productsIdsLocked, null);
            $this->runProcessorForParents($productsIdsLocked);
        }

        $this->setJsonContent([
            'messages' => $messages,
        ]);

        return $this->getResult();
    }
}
