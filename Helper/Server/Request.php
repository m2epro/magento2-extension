<?php

namespace Ess\M2ePro\Helper\Server;

class Request
{
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $helperServerMaintenance;
    /** @var \Ess\M2ePro\Helper\Server */
    private $helperServer;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $helperModuleLogger;

    /**
     * @param \Ess\M2ePro\Helper\Server\Maintenance $helperServerMaintenance
     * @param \Ess\M2ePro\Helper\Server $helperServer
     * @param \Ess\M2ePro\Helper\Module\Logger $helperModuleLogger
     * @param \Ess\M2ePro\Helper\Module\Translation $helperModuleTranslation
     * @param \Ess\M2ePro\Helper\Module\Support $helperModuleSupport
     */
    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $helperServerMaintenance,
        \Ess\M2ePro\Helper\Server $helperServer,
        \Ess\M2ePro\Helper\Module\Logger $helperModuleLogger
    ) {
        $this->helperServerMaintenance = $helperServerMaintenance;
        $this->helperServer = $helperServer;
        $this->helperModuleLogger = $helperModuleLogger;
    }

    public function single(
        array $package,
        $canIgnoreMaintenance = false
    ): array {
        if (!$canIgnoreMaintenance && $this->helperServerMaintenance->isNow()) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The action is temporarily unavailable. M2E Pro Server is under maintenance. Please try again later.'
            );
        }

        $curlObject = $this->buildCurlObject($package, $this->helperServer->getEndpoint());
        // @codingStandardsIgnoreLine
        $responseBody = curl_exec($curlObject);

        // @codingStandardsIgnoreStart
        $response = [
            'body'               => $responseBody,
            'curl_error_number'  => curl_errno($curlObject),
            'curl_error_message' => curl_error($curlObject),
            'curl_info'          => curl_getinfo($curlObject),
        ];
        // @codingStandardsIgnoreEnd

        // @codingStandardsIgnoreLine
        curl_close($curlObject);

        if ($response['body'] === false) {
            $this->helperModuleLogger->process(
                [
                    'curl_error_number'  => $response['curl_error_number'],
                    'curl_error_message' => $response['curl_error_message'],
                    'curl_info'          => $response['curl_info'],
                ],
                'Curl Empty Response'
            );

            throw new \Ess\M2ePro\Model\Exception\Connection(
                (string) __(
                    'M2E Pro Server connection failed. Find the solution <a target="_blank" href="%1">here</a>',
                    'https://help.m2epro.com/support/solutions/articles/9000200887'
                ),
                [
                    'curl_error_number'  => $response['curl_error_number'],
                    'curl_error_message' => $response['curl_error_message'],
                    'curl_info'          => $response['curl_info'],
                ]
            );
        }

        return $response;
    }

    public function multiple(
        array $packages,
        $asynchronous = false,
        $canIgnoreMaintenance = false
    ): array {
        if (!$canIgnoreMaintenance && $this->helperServerMaintenance->isNow()) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The action is temporarily unavailable. M2E Pro Server is under maintenance. Please try again later.'
            );
        }

        if (empty($packages)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Packages is empty.');
        }

        $serverHost = $this->helperServer->getEndpoint();

        $responses = [];

        if (count($packages) === 1 || !$asynchronous) {
            foreach ($packages as $key => $package) {
                $curlObject = $this->buildCurlObject($package, $serverHost);
                // @codingStandardsIgnoreLine
                $responseBody = curl_exec($curlObject);

                // @codingStandardsIgnoreStart
                $responses[$key] = [
                    'body'               => $responseBody,
                    'curl_error_number'  => curl_errno($curlObject),
                    'curl_error_message' => curl_error($curlObject),
                    'curl_info'          => curl_getinfo($curlObject),
                ];
                // @codingStandardsIgnoreEnd

                // @codingStandardsIgnoreLine
                curl_close($curlObject);
            }
        } else {
            $curlObjectsPool = [];
            // @codingStandardsIgnoreLine
            $multiCurlObject = curl_multi_init();

            foreach ($packages as $key => $package) {
                $curlObjectsPool[$key] = $this->buildCurlObject($package, $serverHost);
                // @codingStandardsIgnoreLine
                curl_multi_add_handle($multiCurlObject, $curlObjectsPool[$key]);
            }

            do {
                $stillRunning = 0;
                // @codingStandardsIgnoreLine
                curl_multi_exec($multiCurlObject, $stillRunning);

                if ($stillRunning) {
                    // @codingStandardsIgnoreLine
                    curl_multi_select($multiCurlObject, 1); //sleep in sec.
                }
            } while ($stillRunning > 0);

            foreach ($curlObjectsPool as $key => $curlObject) {
                // @codingStandardsIgnoreStart
                $responses[$key] = [
                    'body'               => curl_multi_getcontent($curlObject),
                    'curl_error_number'  => curl_errno($curlObject),
                    'curl_error_message' => curl_error($curlObject),
                    'curl_info'          => curl_getinfo($curlObject),
                ];

                curl_multi_remove_handle($multiCurlObject, $curlObject);
                curl_close($curlObject);
            }

            curl_multi_close($multiCurlObject);
        }
        // @codingStandardsIgnoreEnd

        foreach ($responses as $response) {
            if ($response['body'] === false) {
                $this->helperModuleLogger->process(
                    [
                        'curl_error_number'  => $response['curl_error_number'],
                        'curl_error_message' => $response['curl_error_message'],
                        'curl_info'          => $response['curl_info'],
                    ],
                    'Curl Empty Response'
                );
                break;
            }
        }

        return $responses;
    }

    private function buildCurlObject(
        $package,
        $serverHost
    ) {
        // @codingStandardsIgnoreLine
        $curlObject = curl_init();

        $preparedHeaders = [];
        if (!empty($package['headers'])) {
            foreach ($package['headers'] as $headerName => $headerValue) {
                $preparedHeaders[] = $headerName . ':' . $headerValue;
            }
        }

        $postData = [];
        if (!empty($package['data'])) {
            $postData = $package['data'];
        }

        $timeout = 300;
        if (isset($package['timeout'])) {
            $timeout = (int)$package['timeout'];
        }

        // @codingStandardsIgnoreLine
        curl_setopt_array(
            $curlObject,
            [
                // set the server we are using
                CURLOPT_URL            => $serverHost,

                // stop CURL from verifying the peer's certificate
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,

                // disable http headers
                CURLOPT_HEADER         => false,

                // set the headers using the array of headers
                CURLOPT_HTTPHEADER     => $preparedHeaders,

                // set the data body of the request
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($postData, '', '&'),

                // set it to return the transfer as a string from curl_exec
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => $timeout,
            ]
        );

        return $curlObject;
    }
}
