<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description\CheckMagentoProductId
 */
class CheckMagentoProductId extends Description
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id', -1);

        $this->setJsonContent([
            'result' => $this->isMagentoProductExists($productId)
        ]);

        return $this->getResult();
    }
}
