<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Description\SnapshotBuilder
 * @method \Ess\M2ePro\Model\Template\Description getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->getModel()->getData();

        if ($this->getModel()->getChildObject() !== null) {
            $data = array_merge($data, $this->getModel()->getChildObject()->getData());
        }

        if (empty($data)) {
            return [];
        }

        $data['specifics'] = $this->getModel()->getChildObject()->getSpecifics();
        $data['definition'] = $this->getModel()->getChildObject()->getDefinitionTemplate()
            ? $this->getModel()->getChildObject()->getDefinitionTemplate()->getData() : [];

        $ignoredKeys = [
            'id', 'template_description_id',
            'update_date', 'create_date',
        ];

        foreach ($data['specifics'] as &$specificsData) {
            foreach ($specificsData as $key => &$value) {
                if (in_array($key, $ignoredKeys)) {
                    unset($specificsData[$key]);
                    continue;
                }

                $value !== null && !is_array($value) && $value = (string)$value;
            }
        }

        unset($value);

        foreach ($data['definition'] as $key => &$value) {
            if (in_array($key, $ignoredKeys)) {
                unset($data['definition'][$key]);
                continue;
            }

            if (is_numeric($value) && $value == 0.0) {
                $value = (float)$value;
            }
        }

        return $data;
    }

    //########################################
}
