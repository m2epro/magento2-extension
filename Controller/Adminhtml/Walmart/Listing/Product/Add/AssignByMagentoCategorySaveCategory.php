<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class AssignByMagentoCategorySaveCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    public function execute()
    {
        $templateId = $this->getRequest()->getParam('template_id');
        $magentoCategoryIds = $this->getRequest()->getParam('magento_categories_ids');

        if (empty($templateId) || empty($magentoCategoryIds)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        if (!is_array($magentoCategoryIds)) {
            $magentoCategoryIds = array_filter(explode(',', $magentoCategoryIds));
        }
        $templatesData = $this->getListing()->getSetting('additional_data', 'adding_product_type_data', []);

        foreach ($magentoCategoryIds as $magentoCategoryId) {
            $templatesData[$magentoCategoryId] = $templateId;
        }

        $this->getListing()->setSetting('additional_data', 'adding_product_type_data', $templatesData);
        $this->getListing()->save();

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }
}
