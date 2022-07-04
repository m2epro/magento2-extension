<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\ViewVariationsSettingsAjax
 */
class ViewVariationsSettingsAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $settings = $this->getLayout()
         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Settings\Form::class);
        $settings->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setJsonContent([
            'html' => $settings->toHtml()
        ]);
        return $this->getResult();
    }
}
