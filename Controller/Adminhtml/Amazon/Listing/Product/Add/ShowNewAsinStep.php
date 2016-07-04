<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class ShowNewAsinStep extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    public function execute()
    {
        $showNewAsinStep = (int)$this->getRequest()->getParam('show_new_asin_step', 1);

        $remember = $this->getRequest()->getParam('remember');

        if ($remember) {
            $this->getListing()->setSetting('additional_data', 'show_new_asin_step', $showNewAsinStep)->save();
        }

        $this->setJsonContent([
            'redirect' => $this->getUrl('*/*/index', [
                'id' => $this->getRequest()->getParam('id'),
                'step' => $showNewAsinStep ? 4 : 5,
                'wizard' => $this->getRequest()->getParam('wizard')
            ])
        ]);

        return $this->getResult();
    }
}