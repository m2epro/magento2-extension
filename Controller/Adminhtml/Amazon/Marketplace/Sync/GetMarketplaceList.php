<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync;

class GetMarketplaceList extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace implements
    \Magento\Framework\App\Action\HttpGetActionInterface
{
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        $result = [];
        foreach ($this->amazonMarketplaceRepository->findWithAccounts() as $marketplace) {
            $result[] = [
                'id' => (int)$marketplace->getId(),
                'title' => $marketplace->getTitle(),
            ];
        }

        return $this->jsonResultFactory->create()
                                       ->setData(['list' => $result]);
    }
}
