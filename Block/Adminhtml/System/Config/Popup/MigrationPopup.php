<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Popup;

class MigrationPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupport;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Support $moduleSupport
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Support $moduleSupport,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->moduleSupport = $moduleSupport;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml(): string
    {
        $config = $this->moduleHelper->getConfig();

        $url = $this->moduleSupport->getDocumentationArticleUrl('x/Ov0kB');
        $confirmContent = <<<HTML
<p>You are going to run the migration of M2E Pro data from Magento v1.x to
Magento v2.x. Before you proceed, please read the <a href="{$url}" target="_blank">Migration Guide.</a>.</p>

<p>If you are ready to start, please click <strong>Confirm</strong>.
The Migration Wizard will guide you through the required steps.</p>

<p><strong>Note</strong>: Once you confirm the migration running, the process cannot be rolled back.</p>
HTML;

        if ($config->getGroupValue(\Ess\M2ePro\Model\Wizard\MigrationFromMagento1::NICK, 'completed')) {
            /** @var \Magento\Framework\View\Element\Messages $errorBlock */
            $errorBlock = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);

            $errorBlock->addError(
                <<<HTML
<p>Attention! Lately, you have already migrated M2E Pro database from Magento v1.x to Magento v2.x.
If you run the process again, all the changes made to the Module data on Magento v2.x will be lost.</p>

<p>Before you proceed, please make sure that another migration is reasonable.</p>
HTML
            );

            $confirmContent = <<<HTML
{$errorBlock->toHtml()}
$confirmContent
HTML;
        }

        $html = <<<HTML
<div id="migration_confirmation_popup" style="display: none">
    <div id="migration_confirmation_popup_content">
        $confirmContent
    </div>
</div>
HTML;

        return parent::_toHtml() . $html;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $wizardUrl = $this->getUrl('m2epro/wizard_migrationFromMagento1/disableModule');

        $this->css->add(
            <<<CSS
.modal-popup.confirm .modal-inner-wrap #migration_confirmation_popup_content .message {
    background: #ffcccc;
}
CSS
        );

        $this->js->addRequireJs(
            [
                'confirm' => 'Magento_Ui/js/modal/confirm',
                'gp' => 'M2ePro/General/PhpFunctions',
            ],
            <<<JS
startMigrationFromMagento1Wizard = function () {
    confirm({
        title: 'Are you sure ?',
        content: jQuery('#migration_confirmation_popup').html(),
        buttons: [{
            text: 'Cancel',
            class: 'action-secondary action-dismiss',
            click: function (event) {
                this.closeModal(event);
            }
        }, {
            text: 'Confirm',
            class: 'action-primary action-accept',
            click: function (event) {
                this.closeModal(event, true);
            }
        }],
        actions: {
            confirm: function () {
                window.open('$wizardUrl');
            },
            cancel: function () {
                return false;
            }
        }
    });
}
JS
        );
    }
}
