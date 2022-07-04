<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Settings\SkuPopup\Form;
use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\GetSkuPopup
 */
class GetSkuPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(Form::class)
        );

        return $this->getResult();
    }
}
