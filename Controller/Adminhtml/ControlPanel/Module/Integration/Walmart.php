<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration\Walmart
 */
class Walmart extends Command
{
    private $formKey;
    private $csvParser;
    private $phpEnvironmentRequest;
    private $productFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Context $context
    ) {
        $this->formKey = $formKey;
        $this->csvParser = $csvParser;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    //########################################
}
