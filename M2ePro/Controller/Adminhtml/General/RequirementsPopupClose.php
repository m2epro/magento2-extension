<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\RequirementsPopupClose
 */
class RequirementsPopupClose extends Base
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getCacheConfig()->setGroupValue(
            '/view/requirements/popup/',
            'closed',
            1
        );

        $this->setJsonContent(['status' => true]);
        return $this->getResult();
    }

    //########################################
}
