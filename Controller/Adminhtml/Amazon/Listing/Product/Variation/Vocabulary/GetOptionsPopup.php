<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class GetOptionsPopup
 * @package Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary
 */
class GetOptionsPopup extends Main
{
    public function execute()
    {
        $block = $this->createBlock('Amazon_Listing_Product_Variation_VocabularyOptionsPopup');

        $this->setAjaxContent($block);

        return $this->getResult();
    }
}
