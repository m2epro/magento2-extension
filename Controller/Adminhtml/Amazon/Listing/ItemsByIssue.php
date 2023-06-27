<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class ItemsByIssue extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByIssue\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByIssue::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend(__('Items By Issue'));

        return $this->getResult();
    }
}
