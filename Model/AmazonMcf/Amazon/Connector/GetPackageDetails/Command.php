<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector\GetPackageDetails;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    public const PACKAGE_NUMBER_PARAM_KEY = 'package_number';

    protected function getCommand(): array
    {
        return ['fulfillmentOutbound', 'package', 'get'];
    }

    protected function getRequestData(): array
    {
        /** @var int $packageNumber */
        $packageNumber = $this->params[self::PACKAGE_NUMBER_PARAM_KEY];

        return ['package_number' => $packageNumber];
    }

    protected function prepareResponseData(): void
    {
        $response = new \M2E\AmazonMcf\Model\Amazon\Connector\GetPackageDetails\Response();

        $responseData = $this->getResponse()->getResponseData();
        $trackingNumber = $responseData['package']['tracking_number'] ?? null;
        if ($trackingNumber !== null) {
            $response->setTrackingNumber($trackingNumber);
        }

        $this->responseData = $response;
    }
}
