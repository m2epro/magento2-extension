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
            '/debug/exceptions/',
            'filters_mode',
            (int)$data['is_filter_enable']
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/debug/fatal_error/',
            'send_to_server',
            (int)$data['send_to_server']['fatal']
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/debug/exceptions/',
            'send_to_server',
            (int)$data['send_to_server']['exception']
        );

        /**  @var $registryModel \Ess\M2ePro\Model\Registry */
        $registryModel = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            '/exceptions_filters/',
            'key',
            false
        );

        if ($registryModel === null) {
            $registryModel = $this->activeRecordFactory->getObject('Registry');
        }

        $registryModel->addData([
            'key' => '/exceptions_filters/',
            'value' => $this->getHelper('Data')->jsonEncode($data['filters'])
        ])->save();
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
