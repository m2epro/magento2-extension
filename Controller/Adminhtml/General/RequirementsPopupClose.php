<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

class RequirementsPopupClose extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->moduleHelper = $moduleHelper;
    }

    public function execute()
    {
        $this->moduleHelper->getRegistry()->setValue('/view/requirements/popup/closed/', 1);
        $this->setJsonContent(['status' => true]);
        return $this->getResult();
    }
}
