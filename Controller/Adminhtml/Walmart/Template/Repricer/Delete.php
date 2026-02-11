<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Delete extends Template
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $repricerTemplateRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $repricerTemplateRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->repricerTemplateRepository = $repricerTemplateRepository;
    }

    public function execute()
    {
        $templateIds = $this->getRequestIds();

        if (count($templateIds) == 0) {
            $this->messageManager->addError(__('Please select Item(s) to remove.'));

            return $this->_redirect('*/*/index');
        }

        $deleted = $locked = 0;
        foreach ($templateIds as $templateId) {
            $template = $this->repricerTemplateRepository->find((int)$templateId);
            if ($template === null) {
                continue;
            }

            if ($template->isLocked()) {
                $locked++;
            } else {
                $this->repricerTemplateRepository->delete($template);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->messageManager
                ->addSuccess(
                    __(
                        '%amount record(s) were deleted.',
                        ['amount' => $deleted]
                    )
                );
        }

        if ($locked) {
            $this->messageManager
                ->addError(
                    __(
                        '%amount record(s) are used in Listing(s). Policy must not be in use to be deleted.',
                        ['amount' => $locked]
                    )
                );
        }

        return $this->_redirect('*/walmart_template/index');
    }
}
