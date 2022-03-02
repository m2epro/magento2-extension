<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\Exception\Connection;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class ServerConnection implements InspectorInterface
{
    /** @var HelperFactory */
    private $helperFactory;

    /** @var IssueFactory */
    private $issueFactory;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        IssueFactory $issueFactory
    ) {
        $this->helperFactory = $helperFactory;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];

        try {
            $response = $this->helperFactory->getObject('Server_Request')->single(
                ['timeout' => 30],
                null,
                null,
                false,
                false,
                true
            );

            $decoded = $this->helperFactory->getObject('Data')->jsonDecode($response['body']);
            if (empty($decoded['response']['result'])) {
                $issues[] = $this->issueFactory->create(
                    'Connection Failed',
                    $response['curl_info']
                );
            }
        } catch (Connection $exception) {
            $additionalData = $exception->getAdditionalData();
            $curlInfo = [];

            if (!empty($additionalData['curl_info'])) {
                $curlInfo = $additionalData['curl_info'];
            }

            $issues[] = $this->issueFactory->create(
                $exception->getMessage(),
                $curlInfo
            );
        }

        return $issues;
    }

    //########################################
}
