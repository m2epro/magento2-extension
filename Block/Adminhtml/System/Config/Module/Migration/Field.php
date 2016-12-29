<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Module\Migration;

use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $moduleHelper, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $wizardUrl = $this->getUrl('m2epro/wizard_migrationFromMagento1/disableModule');

        $buttonHtml = $this->getLayout()->createBlock('\Magento\Backend\Block\Widget\Button')->setData([
            'label' => 'Proceed',
            'class' => 'action-primary',
            'onclick' => 'startMigrationFromMagento1Wizard()',
            'style' => 'margin-left: 15px;'
        ])->toHtml();

        if ($this->moduleHelper->getConfig()->getGroupValue('migrationFromMagento1', 'completed')) {
            /** @var \Magento\Framework\View\Element\Messages $errorBlock */
            $errorBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Messages');

            $errorBlock->addError(<<<HTML
    <p>Previously, you have already tried to Migrate the data from M2E Pro for Magento v1.x. The next attempt
    to Migrate the data will cause loss of all the changes made in the current Module and the data will be
    rolled back to the Magento v1.x data.</p>

    <p>We strongly do not recommend to make the double migration without the full assurance that it is needed.</p>
HTML
);

            $confirmContent = <<<HTML
    {$errorBlock->toHtml()}

    <p>Data Migration from Magento v1.x to Magento v2.x is an ample and complicated process. It contains both manual
    and automatic actions which are divided into several steps. Thus to make this process easier and faster, the
    Migration Wizard was made. So, you should press <strong>Confirm</strong> button to start passing the steps of the
    Migration Wizard.</p>
HTML;
        } else {
            $confirmContent = <<<HTML
    <p>Data Migration from Magento v1.x to Magento v2.x is an ample and complicated process. It contains both manual
    and automatic actions which are divided into several steps. Thus to make this process easier and faster, the
    Migration Wizard was made. So, you should press <strong>Confirm</strong> button to start passing the steps of the
    Migration Wizard.</p>
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

    private function getMigrationFromMagento1Status() {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH);

        return $this->resourceConnection->getConnection()->fetchOne($select);
    }
}