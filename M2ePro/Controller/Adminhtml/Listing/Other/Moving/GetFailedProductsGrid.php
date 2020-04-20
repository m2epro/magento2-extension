<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving\GetFailedProductsGrid
 */
class GetFailedProductsGrid extends Listing
{
    public function execute()
    {
        $block = $this->createBlock('Listing_Moving_FailedProducts_Grid');

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}
