<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Exceptions
 */
class Exceptions extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'exceptions';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    public function processResponseData(array $data)
    {
        $data = $this->prepareAndCheckReceivedData($data);

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/server/exceptions/',
            'filters',
            (int)$data['is_filter_enable']
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/server/fatal_error/',
            'send',
            (int)$data['send_to_server']['fatal']
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/server/exceptions/',
            'send',
            (int)$data['send_to_server']['exception']
        );

        $this->getHelper('Module')->getRegistry()->setValue('/exceptions_filters/', $data['filters']);
    }

    //########################################

    private function prepareAndCheckReceivedData($data)
    {
        // Send To Server
        // ---------------------------------------
        if (!isset($data['send_to_server']['fatal']) || !is_bool($data['send_to_server']['fatal'])) {
            $data['send_to_server']['fatal'] = true;
        }
        if (!isset($data['send_to_server']['exception']) || !is_bool($data['send_to_server']['exception'])) {
            $data['send_to_server']['exception'] = true;
        }
        // ---------------------------------------

        // Exceptions Filters
        // ---------------------------------------
        if (!isset($data['is_filter_enable']) || !is_bool($data['is_filter_enable'])) {
            $data['is_filter_enable'] = false;
        }

        if (!isset($data['filters']) || !is_array($data['filters'])) {
            $data['filters'] = [];
        }

        $validatedFilters = [];

        $allowedFilterTypes = [
            \Ess\M2ePro\Helper\Module\Exception::FILTER_TYPE_TYPE,
            \Ess\M2ePro\Helper\Module\Exception::FILTER_TYPE_INFO,
            \Ess\M2ePro\Helper\Module\Exception::FILTER_TYPE_MESSAGE
        ];

        foreach ($data['filters'] as $filter) {
            if (!isset($filter['preg_match']) || $filter['preg_match'] == '' ||
                !in_array($filter['type'], $allowedFilterTypes)) {
                continue;
            }

            $validatedFilters[] = $filter;
        }

        $data['filters'] = $validatedFilters;
        // ---------------------------------------

        return $data;
    }

    //########################################
}
