<?php

namespace Ess\M2ePro\Api;

class DataObject extends \Magento\Framework\DataObject
{
    /**
     * @param string $key
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDecodedJsonData(string $key): array
    {
        $data = $this->getData($key);

        if (empty($data)) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        return \Ess\M2ePro\Helper\Json::decode($data, true) ?? [];
    }
}
