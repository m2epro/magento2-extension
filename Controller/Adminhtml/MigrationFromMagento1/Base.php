<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Magento\Backend\App\Action;
use Ess\M2ePro\Helper\Factory as HelperFactory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Base
 */
abstract class Base extends Action
{
    /** @var HelperFactory $helperFactory */
    protected $helperFactory = null;

    /** @var \Magento\Framework\Controller\Result\RawFactory $resultRawFactory  */
    protected $resultRawFactory = null;

    /** @var \Magento\Framework\App\ResourceConnection|null  */
    protected $resourceConnection = null;

    /** @var \Magento\Framework\Controller\Result\Raw $rawResult  */
    protected $rawResult = null;

    /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\Runner */
    protected $migrationRunner;

    //########################################

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\Runner $migrationRunner
    ) {
        $this->helperFactory = $context->getHelperFactory();
        $this->resultRawFactory = $context->getResultRawFactory();
        $this->resourceConnection = $context->getResourceConnection();
        $this->migrationRunner = $migrationRunner;

        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    protected function getRawResult()
    {
        if ($this->rawResult === null) {
            $this->rawResult = $this->resultRawFactory->create();
        }

        return $this->rawResult;
    }

    //########################################

    protected function __()
    {
        return $this->getHelper('Module\Translation')->translate(func_get_args());
    }

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################
}
