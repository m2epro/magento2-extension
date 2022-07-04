<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary\ViewVocabularyAjax
 */
class ViewVocabularyAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $vocabulary = $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Vocabulary::class)
            ->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($vocabulary);

        return $this->getResult();
    }
}
