<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

class GetGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $account = $this->ebayFactory->getObjectLoaded('Account', $id);

        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(
                     \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template\Grid::class,
                     '',
                     [
                         'account' => $account
                     ]
                 )
        );

        return $this->getResult();
    }
}
