<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class RemoveOption extends Main
{
    public function execute()
    {
        $productOption = $this->getRequest()->getParam('product_option');
        $productOptionsGroup = $this->getRequest()->getParam('product_options_group');
        $channelAttr = $this->getRequest()->getParam('channel_attr');

        if (empty($productOption) || empty($productOptionsGroup) || empty($channelAttr)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (!is_array($productOptionsGroup)) {
            $productOptionsGroup = htmlspecialchars_decode($productOptionsGroup);
            $productOptionsGroup = $this->getHelper('Data')->jsonDecode($productOptionsGroup);
        }

        $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');
        $vocabularyHelper->removeOptionFromLocalStorage($productOption, $productOptionsGroup, $channelAttr);

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }
}