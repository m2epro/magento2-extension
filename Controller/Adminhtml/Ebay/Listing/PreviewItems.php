<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class PreviewItems
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
 */
class PreviewItems extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function getLayoutType()
    {
        return self::LAYOUT_BLANK;
    }

    public function execute()
    {
        $this->addContent(
            $this->createBlock('Ebay_Listing_Preview')
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Preview Items')
        );

        return $this->getResult();
    }
}
