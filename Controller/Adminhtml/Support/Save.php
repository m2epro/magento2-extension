<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

class Save extends Support
{
    /** @var \Ess\M2ePro\Helper\Module\Support\Form */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support\Form $supportHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->supportHelper = $supportHelper;
        $this->componentHelper = $componentHelper;
    }

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
            'description',
        ];

        $components = $this->componentHelper->getEnabledComponents();
        if (count($components) === 1) {
            $post['component'] = array_pop($components);
        }

        $data = [];
        foreach ($keys as $key) {
            if (!isset($post[$key])) {
                $this->messageManager->addError($this->__('You should fill in all required fields.'));

                return $this->_redirect('*/*/index');
            }
            $data[$key] = $post[$key];
        }

        $this->supportHelper->send(
            $data['component'],
            $data['contact_mail'],
            $data['contact_name'],
            $data['subject'],
            $data['description']
        );

        $this->messageManager->addSuccess($this->__('Your message has been sent.'));

        $referrer = $this->getRequest()->getParam('referrer', false);
        $params   = [];
        $referrer && $params['referrer'] = $referrer;

        return $this->_redirect('*/*/index', $params);
    }
}
