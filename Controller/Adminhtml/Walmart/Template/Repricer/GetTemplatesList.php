<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

class GetTemplatesList extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Template
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->templateRepricerRepository = $templateRepricerRepository;
    }

    public function execute()
    {
        $result = [
            ['id' => '', 'title' => __('None')]
        ];

        foreach ($this->templateRepricerRepository->getAllSortedByTitle() as $repricerTemplate) {
            $result[] = [
                'id' => $repricerTemplate->getId(),
                'title' => $repricerTemplate->getTitle(),
            ];
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
