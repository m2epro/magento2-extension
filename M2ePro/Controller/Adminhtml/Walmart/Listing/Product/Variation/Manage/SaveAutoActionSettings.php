<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage\SaveAutoActionSettings
 */
class SaveAutoActionSettings extends Main
{
    public function execute()
    {
        $attributeAutoAction = $this->getRequest()->getParam('attribute_auto_action');
        $optionAutoAction = $this->getRequest()->getParam('option_auto_action');

        if ($attributeAutoAction === null || $optionAutoAction === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $vocabularyHelper = $this->getHelper('Component_Walmart_Vocabulary');

        switch ($attributeAutoAction) {
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $vocabularyHelper->unsetAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $vocabularyHelper->disableAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $vocabularyHelper->enableAttributeAutoAction();
                break;
        }

        switch ($optionAutoAction) {
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $vocabularyHelper->unsetOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $vocabularyHelper->disableOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Walmart\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $vocabularyHelper->enableOptionAutoAction();
                break;
        }

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }
}
