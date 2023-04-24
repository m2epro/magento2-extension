<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Template\Get;

class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['account', 'get', 'shippingTemplatesInfo'];
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['templates']) || !is_array($responseData['templates'])) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function prepareResponseData(): void
    {
        $preparedData = [];

        $response = $this->getResponse()->getResponseData();

        foreach ($response['templates'] as $template) {
            $preparedData['templates'][] = [
                'account_id' => $this->account->getId(),
                'template_id' => $template['id'],
                'title' => $template['name'],
            ];
        }

        $this->responseData = $preparedData;
    }
}
