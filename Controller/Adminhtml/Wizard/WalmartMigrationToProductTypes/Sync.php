<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\WalmartMigrationToProductTypes;

class Sync extends \Ess\M2ePro\Controller\Adminhtml\Wizard\AbstractWalmartMigrationToProductTypes
{
    private \Ess\M2ePro\Model\Walmart\Marketplace\WithProductType\ForceAllSynchronization $forceAllSync;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\WithProductType\ForceAllSynchronization $forceAllSync,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->forceAllSync = $forceAllSync;
        $this->exceptionHelper = $exceptionHelper;
    }

    public function execute()
    {
        try {
            $this->forceAllSync->process();
        } catch (\Throwable $exception) {
            $this->exceptionHelper->process($exception);
        }

        $this->setStatus(\Ess\M2ePro\Helper\Module\Wizard::STATUS_SKIPPED);

        return $this->_redirect("*/walmart_listing/index");
    }
}
