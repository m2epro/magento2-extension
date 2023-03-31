<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections;

class MigrationWizard extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $wizardUrl = $this->getUrl('m2epro/wizard_migrationFromMagento1/database');

        $this->js->add(
            <<<JS
window.location.href = "$wizardUrl";
JS
        );
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
