<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

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
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Vocabulary::class)
            ->setListingProduct($this->walmartFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($vocabulary);

        return $this->getResult();
    }
}
