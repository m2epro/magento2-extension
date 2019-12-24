<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Support\Save
 */
class Save extends Support
{
    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getParams()) {
            return $this->_redirect('*/*/index');
        }

        $keys = [
            'component',
            'contact_mail',
            'contact_name',
            'subject',
            'description'
        ];

        $components = $this->getHelper('Component')->getEnabledComponents();
        count($components) == 1 && $post['component'] = array_pop($components);

        $data = [];
        foreach ($keys as $key) {
            if (!isset($post[$key])) {
                $this->messageManager->addError($this->__('You should fill in all required fields.'));
                return $this->_redirect('*/*/index');
            }
            $data[$key] = $post[$key];
        }

        $severity = isset($post['severity']) ? $post['severity'] : null;

        $this->getHelper('Module_Support_Form')->send(
            $data['component'],
            $data['contact_mail'],
            $data['contact_name'],
            $data['subject'],
            $data['description'],
            $severity
        );

        $this->messageManager->addSuccess($this->__('Your message has been successfully sent.'));

        $referrer = $this->getRequest()->getParam('referrer', false);
        $params = [];
        $referrer && $params['referrer'] = $referrer;

        return $this->_redirect('*/*/index', $params);
    }

    //########################################
}
