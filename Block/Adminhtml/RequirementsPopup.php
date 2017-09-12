<?php

namespace Ess\M2ePro\Block\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class RequirementsPopup extends AbstractBlock
{
    protected $_template = 'requirements_popup.phtml';

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Confirm and Close' => $this->__('Confirm and Close'),
            'System Requirements' => $this->__('System Requirements'),
            'System Configuration does not meet minimum requirements. Please check.' => $this->__(
                'M2E Pro has detected, that your System Configuration does not meet minimum requirements,
                so there might be problems with its work.
                Please <a href="javascript:" onclick="RequirementsPopupObj.show()">check it</a>
                and amend appropriate Settings.'
            ),
        ]);

        $this->jsUrl->addUrls([
            'general/requirementsPopupClose' => $this->getUrl('*/general/requirementsPopupClose'),
        ]);

        $this->js->addOnReadyJs(<<<JS
require([
    'M2ePro/RequirementsPopup'
], function(){

    window.RequirementsPopupObj = new RequirementsPopup();
});
JS
        );

        $block = $this->createBlock('ControlPanel\Inspection\Requirements');
        $this->setChild('requirements', $block);
    }

    //########################################
}