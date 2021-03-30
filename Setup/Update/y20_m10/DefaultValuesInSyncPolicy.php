<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\DefaultValuesInSyncPolicy
 */
class DefaultValuesInSyncPolicy extends AbstractFeature
{
    //########################################

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $addingMessage = false;
        $messages = [];

        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $templates = $this->getConnection()
                ->select()
                ->from($this->getFullTableName("{$component}_template_synchronization"))
                ->where('relist_qty_calculated = ?', 0) // \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE
                ->orWhere('stop_qty_calculated = ?', 0) // \Ess\M2ePro\Model\Template\Synchronization::QTY_MODE_NONE
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
                'type'     => 2, // \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_WARNING
                'text'     => <<<HTML
<a href="https://support.m2epro.com/knowledgebase/1560897" target="_blank">Magento Multi-Source Inventory feature</a>
enabled by default starting from Magento v2.3.
If you’re using the feature now or planning to use it in the future,
it’s highly recommended to reset Relist when Quantity option to Less or Equal to 1 and Stop
When Quantity option to Is 0.
Otherwise, it may <a href="https://support.m2epro.com/knowledgebase/1606824" target="_blank">
affect actual product data updates and lead to overselling</a>.
<br/>
<br/>
<a href="%url_reset%">Confirm</a> the reset of Revise and Stop Rules to default or
<a href="%url_skip%"><b>skip this message</b></a>.
HTML
                ,
                'url_reset' => 'm2epro/template/setDefaultValuesInSyncPolicy',
                'url_skip'  => 'm2epro/template/skipDefaultValuesInSyncPolicy',
                'lifetime' => $now->format('Y-m-d H:i:s')
            ];
        }

        $dataHelper = $this->helperFactory->getObject('Data');
        $this->getConnection()->insert(
            $this->getFullTableName('registry'),
            [
                'key'   => '/upgrade/messages/',
                'value' => $dataHelper->jsonEncode($messages)
            ]
        );
    }

    //########################################
}
