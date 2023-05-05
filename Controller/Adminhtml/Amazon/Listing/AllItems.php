<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class AllItems extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    /**
     * @ingeritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AllItems\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AllItems::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend(__('All Items'));

        return $this->getResult();
    }
}
