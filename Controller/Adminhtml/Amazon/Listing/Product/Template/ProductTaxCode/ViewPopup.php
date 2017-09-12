<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

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

        $messages = array();
        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIdsLocked) < count($productsIds)) {
            $messages[] = array(
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                        'The Product Tax Code Policy was not assigned because the Products have In Action Status.'
                    ). '</p>'
            );
        }

        if (empty($productsIdsLocked)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);
            return $this->getResult();
        }

        $mainBlock = $this->createBlock('Amazon\Listing\Product\Template\ProductTaxCode');

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