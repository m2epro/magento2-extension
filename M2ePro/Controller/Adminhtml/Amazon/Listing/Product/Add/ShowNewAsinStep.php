<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\ShowNewAsinStep
 */
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
