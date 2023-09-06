<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Create extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $amazonConnectorDispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonConnectorDispatcherFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->exceptionHelper = $exceptionHelper;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->amazonConnectorDispatcherFactory = $amazonConnectorDispatcherFactory;
    }

    public function execute()
    {
        /** @var array $requestData */
        $requestData = $this->getRequest()->getPost()->toArray();
        if (empty($requestData)) {
            $this->_forward('index');
        }

        $title = $requestData['title'];
        if (empty($title)) {
            return $this->getFailResult(__('Title can\'t be empty.'));
        }

        if (!$this->isAccountTitleUnique($title)) {
            return $this->getFailResult(__('Title must be unique.'));
        }

        $marketplace = $this->getMarketplace((int)$requestData['marketplace_id']);
        if (empty($marketplace)) {
            return $this->getFailResult(__('Unable to create account for this marketplace.'));
        }

        try {
            $authUrl = $this->getAuthUrl($title, $marketplace);
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            return $this->getFailResult(
                __(
                    'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message',
                    $exception->getMessage()
                )
            );
        }

        $this->setJsonContent([
            'success' => true,
            'url' => $authUrl,
        ]);

        return $this->getResult();
    }

    /**
     * @param string $message
     *
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\View\Result\Page
     */
    private function getFailResult(string $message)
    {
        $this->setJsonContent([
            'success' => false,
            'message' => $message,
        ]);

        return $this->getResult();
    }

    /**
     * @param string $title
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return string
     */
    private function getAuthUrl(string $title, \Ess\M2ePro\Model\Marketplace $marketplace): string
    {
        $backUrl = $this->getUrl(
            '*/*/afterGetToken',
            [
                'marketplace_id' => $marketplace->getId(),
                'title' => rawurlencode($title),
            ]
        );

        $dispatcher = $this->amazonConnectorDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Get\AuthUrlRequester  $connectorObj */
        $connectorObj = $dispatcher->getConnector(
            'account',
            'get',
            'authUrlRequester',
            ['back_url' => $backUrl, 'marketplace_native_id' => $marketplace->getNativeId()]
        );

        $dispatcher->process($connectorObj);
        return $connectorObj->getAuthUrlFromResponseData();
    }

    /**
     * @param int $marketplaceId
     *
     * @return \Ess\M2ePro\Model\Marketplace|null
     */
    private function getMarketplace(int $marketplaceId): ?\Ess\M2ePro\Model\Marketplace
    {
        $marketplaceCollection = $this->marketplaceCollectionFactory->create();
        $marketplaceCollection->addFieldToFilter('id', $marketplaceId)
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $marketplaceCollection->getFirstItem();

        return $marketplace->getId() ? $marketplace : null;
    }

    /**
     * @param string $title
     *
     * @return bool
     */
    private function isAccountTitleUnique(string $title): bool
    {
        $collection = $this->accountCollectionFactory->create();
        $collection->addFieldToFilter('title', $title);

        return !$collection->getSize();
    }
}
