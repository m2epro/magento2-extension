<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category\Assign
 */
class Assign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category
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

        $msgType = 'success';
        $messages = [];

        $this->setCategoryTemplateForProducts($productsIds, $templateId);
        $this->runProcessorForParents($productsIds);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded('Walmart_Template_Category', $templateId);
        $template->setSynchStatusNeed($template->getDataSnapshot(), []);

        $messages[] = $this->__(
            'Category Policy was successfully assigned to %count% Products',
            count($productsIds)
        );

        $this->setJsonContent([
            'type' => $msgType,
            'messages' => $messages,
            'products_ids' => implode(',', $productsIds)
        ]);

        return $this->getResult();
    }
}
