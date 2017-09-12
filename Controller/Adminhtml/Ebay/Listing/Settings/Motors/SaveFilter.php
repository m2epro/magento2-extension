<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

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

        $data = array(
            'title' => $post['title'],
            'type' => $post['type'],
            'note' => $post['note'],
            'conditions' => $this->getHelper('Data')->jsonEncode($post['conditions']),
        );

        $model = $this->activeRecordFactory->getObject('Ebay\Motor\Filter');
        $model->addData($data)->save();

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}