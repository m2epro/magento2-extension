<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category\SnapshotBuilder
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->model->getData();
        if (empty($data)) {
            return [];
        }

        $data['specifics'] = $this->model->getSpecifics();

        $ignoredKeys = [
            'id', 'title', 'template_category_id',
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

        return $data;
    }

    //########################################
}
