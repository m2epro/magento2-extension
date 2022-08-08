<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class AfterToken extends \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Create */
    private $serverAccountCreate;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $logger;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate
     * @param \Ess\M2ePro\Helper\Data\Session $sessionHelper
     * @param \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
     * @param \Ess\M2ePro\Helper\Module\Logger $logger
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper
     * @param \Magento\Framework\Code\NameBuilder $nameBuilder
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\Logger $logger,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);

        $this->sessionHelper = $sessionHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->serverAccountCreate = $serverAccountCreate;
        $this->logger = $logger;
        $this->accountBuilder = $accountBuilder;
    }

    public function execute()
    {
        try {
            $amazonData = $this->getAmazonData();
            if ($amazonData === null) {
                return $this->indexAction();
            }

            $marketplaceId = $this->sessionHelper->getValue('marketplace_id', true);
            if ($marketplaceId === null) {
                $this->logger->process('Unable to retrieve marketplace from session.', 'wizard');

                return $this->indexAction();
            }
        } catch (\LogicException $exception) {
            $this->messageManager->addError($this->__($exception->getMessage()));

            return $this->indexAction();
        }

        try {
            $result = $this->serverAccountCreate->process(
                $amazonData['token'],
                $amazonData['merchant'],
                (int)$marketplaceId
            );
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $this->messageManager->addError($this->__($exception->getMessage()));

            return $this->indexAction();
        }

        $this->createAccount(
            $amazonData['merchant'],
            $amazonData['merchant'],
            $marketplaceId,
            $result
        );

        $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId)
                                  ->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                                  ->save();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    // ----------------------------------------

    /**
     * @return array{merchant:string, token:string}|null
     */
    private function getAmazonData(): ?array
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return null;
        }

        $requiredFields = [
            'Merchant',
            'MWSAuthToken',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                throw new \LogicException($this->__('The Amazon token obtaining is currently unavailable.'));
            }
        }

        return [
            'merchant' => $params['Merchant'],
            'token'    => $params['MWSAuthToken'],
        ];
    }

    /**
     * @param string $title
     * @param string $merchantId
     * @param int $marketplaceId
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result $serverResult
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createAccount(
        string $title,
        string $merchantId,
        int $marketplaceId,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result $serverResult
    ): void {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObject('Account');

        $data = $this->accountBuilder->getDefaultData();

        // region prepare data
        $data['magento_orders_settings']['tax']['excluded_states'] = implode(
            ',',
            $data['magento_orders_settings']['tax']['excluded_states']
        );

        $data['magento_orders_settings']['tax']['excluded_countries'] = implode(
            ',',
            $data['magento_orders_settings']['tax']['excluded_countries']
        );
        // endregion

        $data['title'] = $title;
        $data['merchant_id'] = $merchantId;
        $data['marketplace_id'] = $marketplaceId;
        $data['server_hash'] = $serverResult->getHash();
        $data['info'] = $serverResult->getInfo();

        $this->accountBuilder->build(
            $account,
            $data
        );
    }
}
