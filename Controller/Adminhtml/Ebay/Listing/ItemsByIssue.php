<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class ItemsByIssue extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /**
     * @ingeritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByIssue\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByIssue::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Items By Issue'));

        return $this->getResult();
    }
}
