<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class GetStrategies extends Template
{
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\AccountStrategiesLoader $accountStrategiesLoader;
    private \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\AccountStrategiesLoader $accountStrategiesLoader,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->walmartAccountRepository = $walmartAccountRepository;
        $this->accountStrategiesLoader = $accountStrategiesLoader;
    }

    public function execute()
    {
        $accountRepricerStrategies = $this->accountStrategiesLoader->execute(
            $this->walmartAccountRepository->get((int)$this->getRequest()->getParam('accountId')),
            (bool)$this->getRequest()->getParam('force_load')
        );

        $this->setJsonContent(array_map(function ($strategy) {
            return [
                'id' => $strategy->id,
                'title' => $strategy->title,
            ];
        }, $accountRepricerStrategies));

        return $this->getResult();
    }
}
