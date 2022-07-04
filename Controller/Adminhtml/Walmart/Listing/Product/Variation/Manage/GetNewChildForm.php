<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations\NewChild\Form;
use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class GetNewChildForm extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $form = $this->getLayout()->createBlock(Form::class);
        $form->setListingProduct($this->walmartFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($form);

        return $this->getResult();
    }
}
