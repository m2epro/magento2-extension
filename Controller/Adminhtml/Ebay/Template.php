<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
 */
abstract class Template extends Main
{
    protected $templateManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    ) {
        $this->templateManager = $templateManager;
        parent::__construct($ebayFactory, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_templates');
    }

    //########################################
}
