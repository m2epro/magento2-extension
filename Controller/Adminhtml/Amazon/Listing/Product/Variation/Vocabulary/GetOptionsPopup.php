<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetOptionsPopup extends Main
{
    public function execute()
    {
        $block = $this->createBlock('Amazon\Listing\Product\Variation\VocabularyOptionsPopup');

        $this->setAjaxContent($block);

        return $this->getResult();
    }
}