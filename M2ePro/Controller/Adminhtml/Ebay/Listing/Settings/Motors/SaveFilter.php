<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\SaveFilter
 */
class SaveFilter extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $post = (array)$this->getRequest()->getPost();
        parse_str($post['conditions'], $post['conditions']);

        foreach ($post['conditions'] as $key => $value) {
            if ($value == '' || $key == 'massaction') {
                unset($post['conditions'][$key]);
            }
        }

        $data = [
            'title' => $post['title'],
            'type' => $post['type'],
            'note' => $post['note'],
            'conditions' => $this->getHelper('Data')->jsonEncode($post['conditions']),
        ];

        $model = $this->activeRecordFactory->getObject('Ebay_Motor_Filter');
        $model->addData($data)->save();

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
