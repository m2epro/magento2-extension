<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

class Prepare extends Base
{
    /** @var \Magento\Framework\App\ResourceConnection|null  */
    protected $resourceConnection = NULL;

    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory  */
    protected $resultPageFactory = NULL;

    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    protected $helperFactory = NULL;

    //########################################

    public function __construct(\Ess\M2ePro\Controller\Adminhtml\Context $context)
    {
        $this->resourceConnection = $context->getResourceConnection();
        $this->resultPageFactory = $context->getResultPageFactory();
        $this->helperFactory = $context->getHelperFactory();

        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $this->getRawResult()->setContents('Current version of M2E Pro does not support migration from Magento v1.x');
        return $this->getRawResult();
    }

    //########################################
}