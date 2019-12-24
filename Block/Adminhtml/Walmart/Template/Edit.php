<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Edit
 */
abstract class Edit extends AbstractContainer
{
    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Do not show any more' => $this->__('Do not show this message anymore'),
            'Save Policy' => $this->__('Save Policy')
        ]);

        return parent::_beforeToHtml();
    }

    protected function getSaveConfirmationText($id = null)
    {
        $saveConfirmation = '';
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        if ($id === null && $template !== null) {
            $id = $template->getId();
        }

        if ($id) {
            $saveConfirmation = $this->getHelper('Data')->escapeJs(
                $this->__('<br/>
<b>Note:</b> All changes you have made will be automatically
applied to all M2E Pro Listings where this Policy is used.')
            );
        }

        return $saveConfirmation;
    }
}
