<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Finish
 */
class Finish extends Base
{
    //########################################

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

        if ($components = $this->getHelper('Component')->getEnabledComponents()) {
            $component = reset($components);

            return $this->_redirect($this->getUrl("*/{$component}_listing/index"));
        } else {
            return $this->_redirect($this->getHelper('Module\Support')->getPageRoute());
        }
    }

    //########################################

    /**
     * @throws \Exception
     */
    private function addDefaultValuesInSyncPolicyMessage()
    {
        // \Ess\M2ePro\Setup\Update\y20_m10\DefaultValuesInSyncPolicy::class
        $messages = $this->getHelper('Module')->getUpgradeMessages();
        $addingMessage = false;

        $connection = $this->resourceConnection->getConnection();
        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $templates = $connection
                ->select()
                ->from($this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix("m2epro_{$component}_template_synchronization"))
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
                    $this->getHelper('Module_Support')->getKnowledgebaseUrl('1560897'),
                    $this->getHelper('Module_Support')->getKnowledgebaseUrl('1606824')
                ),
                'url_reset' => 'm2epro/template/setDefaultValuesInSyncPolicy',
                'url_skip'  => 'm2epro/template/skipDefaultValuesInSyncPolicy',
                'lifetime'  => $now->format('Y-m-d H:i:s')
            ];
        }

        $this->getHelper('Module')->getRegistry()->setValue('/upgrade/messages/', $messages);
    }

    //########################################
}
