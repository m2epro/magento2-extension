<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\MigrationWizard;

class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $wizardUrl = $this->getUrl('m2epro/wizard_migrationFromMagento1/database');

        return <<<HTML
<script>
    window.location.href = "{$wizardUrl}";
</script>
HTML;
    }
}