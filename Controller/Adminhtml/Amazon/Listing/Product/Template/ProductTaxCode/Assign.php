<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

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

        $messages = array();
        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIdsLocked) < count($productsIds)) {
            $messages[] = array(
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                        'Product Tax Code Policy cannot be assigned to some Products
                         because the Products are in Action'). '</p>'
            );
        }

        if (!empty($productsIdsLocked)) {

            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Product Tax Code Policy was successfully assigned.')
            );

            $this->setProductTaxCodeTemplateForProducts($productsIdsLocked, $templateId);
            $this->runProcessorForParents($productsIdsLocked);

            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $template */
            $template = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ProductTaxCode', $templateId);
            $template->setSynchStatusNeed($template->getDataSnapshot(),array());
        }

        $this->setJsonContent([
            'messages' => $messages
        ]);
        return $this->getResult();
    }
}