<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\AssignByMagentoCategorySaveCategory
 */
class AssignByMagentoCategorySaveCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $templateId = $this->getRequest()->getParam('template_id');
        $magentoCategoryIds = $this->getRequest()->getParam('magento_categories_ids');

        if (empty($templateId) || empty($magentoCategoryIds)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        !is_array($magentoCategoryIds) && $magentoCategoryIds = array_filter(explode(',', $magentoCategoryIds));
        $templatesData = $this->getListing()->getSetting('additional_data', 'adding_category_templates_data', []);

        foreach ($magentoCategoryIds as $magentoCategoryId) {
            $templatesData[$magentoCategoryId] = $templateId;
        }

        $this->getListing()->setSetting('additional_data', 'adding_category_templates_data', $templatesData);
        $this->getListing()->save();

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}
