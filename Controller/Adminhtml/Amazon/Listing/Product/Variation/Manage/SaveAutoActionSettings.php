<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SaveAutoActionSettings extends Main
{
    public function execute()
    {
        $attributeAutoAction = $this->getRequest()->getParam('attribute_auto_action');
        $optionAutoAction = $this->getRequest()->getParam('option_auto_action');

        if (is_null($attributeAutoAction) || is_null($optionAutoAction)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');

        switch($attributeAutoAction) {
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $vocabularyHelper->unsetAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $vocabularyHelper->disableAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $vocabularyHelper->enableAttributeAutoAction();
                break;
        }

        switch($optionAutoAction) {
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $vocabularyHelper->unsetOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $vocabularyHelper->disableOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $vocabularyHelper->enableOptionAutoAction();
                break;
        }

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }
}