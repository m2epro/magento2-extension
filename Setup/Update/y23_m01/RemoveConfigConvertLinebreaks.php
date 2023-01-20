<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

class RemoveConfigConvertLinebreaks extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     */
    public function execute()
    {
        $config = $this->getConfigModifier('module');

        $config->delete(
            '/general/configuration/',
            'renderer_description_convert_linebreaks_mode'
        );
    }
}
