<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceData;

abstract class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['data']);
    }
}
