<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

class Finish extends Base
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\Runner $migrationRunner
    ) {
        parent::__construct($context, $migrationRunner);

        $this->moduleHelper = $moduleHelper;
        $this->supportHelper = $supportHelper;
        $this->componentHelper = $componentHelper;
        $this->dbStructureHelper = $dbStructureHelper;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
            '/cron/',
            'mode',
            $this->getRequest()->getParam('enable_synchronization') ? 1 : 0
        );

        $this->addDefaultValuesInSyncPolicyMessage();

        if ($components = $this->componentHelper->getEnabledComponents()) {
            $component = reset($components);

            return $this->_redirect($this->getUrl("*/{$component}_listing/index"));
        } else {
            return $this->_redirect($this->supportHelper->getPageRoute());
        }
    }

    /**
     * @throws \Exception
     */
    private function addDefaultValuesInSyncPolicyMessage()
    {
        // \Ess\M2ePro\Setup\Update\y20_m10\DefaultValuesInSyncPolicy::class
        $messages = $this->moduleHelper->getUpgradeMessages();
        $addingMessage = false;

        $connection = $this->resourceConnection->getConnection();
        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $templates = $connection
                ->select()
                ->from($this->dbStructureHelper->getTableNameWithPrefix("m2epro_{$component}_template_synchronization"))
                ->where('relist_qty_calculated = ?', \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE)
                ->orWhere('stop_qty_calculated = ?', \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE)
                ->query()
                ->fetchAll();

            if (empty($templates)) {
                continue;
            }

            $addingMessage = true;
            break;
        }

        if ($addingMessage) {

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $now->modify('+7 days');

            $messages['default_values_in_sync_policy'] = [
                'type'      => \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_WARNING,
                'text'      => $this->__(
                    <<<HTML
<a href="%url_1%" target="_blank">Magento Multi-Source Inventory feature</a>
enabled by default starting from Magento v2.3.
If you’re using the feature now or planning to use it in the future,
it’s highly recommended to reset Relist when Quantity option to Less or Equal to 1 and Stop
When Quantity option to Is 0.
Otherwise, it may <a href="%url_2%" target="_blank">affect actual product data updates and lead to overselling</a>.
<br/>
<br/>
<a href="%url_reset%">Confirm</a> the reset of Revise and Stop Rules to default or
<a href="%url_skip%"><b>skip this message</b></a>.
HTML
                    ,
                    $this->supportHelper->getKnowledgebaseUrl('1560897'),
                    $this->supportHelper->getKnowledgebaseUrl('1606824')
                ),
                'url_reset' => 'm2epro/template/setDefaultValuesInSyncPolicy',
                'url_skip'  => 'm2epro/template/skipDefaultValuesInSyncPolicy',
                'lifetime'  => $now->format('Y-m-d H:i:s')
            ];
        }

        $this->moduleHelper->getRegistry()->setValue('/upgrade/messages/', $messages);
    }
}
