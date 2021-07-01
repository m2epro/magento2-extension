<?php

/*
* @author     M2E Pro Developers Team
* @copyright  M2E LTD
* @license    Commercial use is forbidden
*/

namespace Ess\M2ePro\Setup\Update\y21_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m06\FixBrokenUrl
 */
class FixBrokenUrl extends AbstractFeature
{
    public function execute()
    {
        /** @var  \Ess\M2ePro\Model\Registry\Manager $registryManager*/
        $registryManager = $this->modelFactory->getObject('Registry_Manager');

        $messages = $registryManager->getValueFromJson('/upgrade/messages/');
        if (empty($messages) || empty($messages['default_values_in_sync_policy'])) {
            return;
        }

        $messages['default_values_in_sync_policy']['text'] = <<<HTML
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
HTML;
        $messages['default_values_in_sync_policy']['url_reset'] = 'm2epro/template/setDefaultValuesInSyncPolicy';
        $messages['default_values_in_sync_policy']['url_skip'] = 'm2epro/template/skipDefaultValuesInSyncPolicy';

        $registryManager->setValue('/upgrade/messages/', $messages);
    }
}
