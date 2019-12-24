<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode\ViewPopup
 */
class ViewPopup extends ProductTaxCode
{
    public function execute()
    {
        $productsIds  = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.');
            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = [];
        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIdsLocked) < count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                    'The Product Tax Code Policy was not assigned because the Products have In Action Status.'
                ). '</p>'
            ];
        }

        if (empty($productsIdsLocked)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);
            return $this->getResult();
        }

        $mainBlock = $this->createBlock('Amazon_Listing_Product_Template_ProductTaxCode');

        if (!empty($messages)) {
            $mainBlock->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $mainBlock->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $productsIdsLocked)
        ]);
        return $this->getResult();
    }
}
