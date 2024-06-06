<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\GetPackageDetails;

class Processor
{
    use \Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\MessagesTrait;

    /** @var \Ess\M2ePro\Model\Amazon\Account\Repository */
    private $amazonAccountRepository;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $dispatcherFactory;
    /** @var \Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\CommandExecutor */
    private $commandExecutor;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository,
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\CommandExecutor $commandExecutor
    ) {
        $this->amazonAccountRepository = $amazonAccountRepository;
        $this->dispatcherFactory = $dispatcherFactory;
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * @return \M2E\AmazonMcf\Model\Amazon\Connector\GetPackageDetails\Response
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\AuthorizationException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\SystemUnavailableException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\ThrottlingException
     */
    public function process(string $merchantId, int $packageNumber)
    {
        $dispatcher = $this->dispatcherFactory->create();
        $accountId = $this->amazonAccountRepository
            ->getFistByMerchantId($merchantId)
            ->getAccountId();

        /** @var Command $command */
        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [Command::PACKAGE_NUMBER_PARAM_KEY => $packageNumber],
            $accountId
        );

        /** @var \M2E\AmazonMcf\Model\Amazon\Connector\GetPackageDetails\Response $response */
        $response = $this->commandExecutor->execute($command);
        if (!empty($messages)) {
            return $response->setMessages($messages);
        }

        $messages = $this->retrieveMcfMessages($command);
        if (!empty($messages)) {
            $response->setMessages($messages);
        }

        return $response;
    }
}
