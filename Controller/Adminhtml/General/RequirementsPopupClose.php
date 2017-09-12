<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\Base;

class RequirementsPopupClose extends Base
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getCacheConfig()->setGroupValue(
            '/view/requirements/popup/', 'closed', 1
        );

        $this->setJsonContent(['status' => true]);
        return $this->getResult();
    }

    //########################################
}