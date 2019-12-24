<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Module\Migration;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\System\Config\Module\Migration\Field
 */
class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    protected $resourceConnection;
    protected $moduleSupport;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Support $moduleSupport,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->moduleSupport = $moduleSupport;

        parent::__construct($context, $moduleHelper, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $wizardUrl = $this->getUrl('m2epro/wizard_migrationFromMagento1/disableModule');

        $buttonHtml = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)->setData([
            'label' => 'Proceed',
            'class' => 'action-primary',
            'onclick' => 'startMigrationFromMagento1Wizard()',
            'style' => 'margin-left: 15px;'
        ])->toHtml();

        $config = $this->moduleHelper->getConfig();

        $url = $this->moduleSupport->getDocumentationArticleUrl('/x/EgA9AQ');
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

            $errorBlock->addError(<<<HTML
    <p>Attention! Lately, you have already migrated M2E Pro database from Magento v1.x to Magento v2.x.
    If you run the process again, all the changes made to the Module data on Magento v2.x will be lost.</p>

    <p>Before you proceed, please make sure that another migration is reasonable.</p>
HTML
            );

            $confirmContent = <<<HTML
    {$errorBlock->toHtml()}

    {$confirmContent}
HTML;
        }

        $html = <<<HTML
<td class="value" colspan="3" style="padding: 2.2rem 1.5rem 0 0;">
    <div style="text-align: center">
        Open Migration Wizard {$buttonHtml}
    </div>
</td>

<style>
    .modal-popup.confirm .modal-inner-wrap #migration_confirmation_popup_content .message {
        background: #ffcccc;
    }
</style>

<div id="migration_confirmation_popup" style="display: none">
    <div id="migration_confirmation_popup_content">
        {$confirmContent}
    </div>
</div>

<script>
    require([
        'Magento_Ui/js/modal/confirm'
    ], function(confirm) {
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
                        window.open('{$wizardUrl}');
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }
    });
</script>
HTML;
        return $this->_decorateRowHtml($element, $html);
    }
}
