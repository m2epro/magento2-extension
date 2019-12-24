<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode\Assign
 */
class Assign extends ProductTaxCode
{
    public function execute()
    {
        $productsIds  = $this->getRequest()->getParam('products_ids');
        $templateId   = $this->getRequest()->getParam('template_id');

        if (empty($productsIds) || empty($templateId)) {
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
                    'Product Tax Code Policy cannot be assigned to some Products
                         because the Products are in Action'
                ). '</p>'
            ];
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = [
                'type' => 'success',
                'text' => $this->__('Product Tax Code Policy was successfully assigned.')
            ];

            $this->setProductTaxCodeTemplateForProducts($productsIdsLocked, $templateId);
            $this->runProcessorForParents($productsIdsLocked);

            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $template */
            $template = $this->activeRecordFactory->getObjectLoaded('Amazon_Template_ProductTaxCode', $templateId);
            $template->setSynchStatusNeed($template->getDataSnapshot(), []);
        }

        $this->setJsonContent([
            'messages' => $messages
        ]);
        return $this->getResult();
    }
}
