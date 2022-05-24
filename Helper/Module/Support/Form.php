<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Support;

class Form
{
    /** @var \Magento\Framework\HTTP\PhpEnvironment\Request */
    private $phpEnvironmentRequest;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    /**
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Component $componentHelper
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     */
    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper
    ) {
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->supportHelper = $supportHelper;
        $this->componentHelper = $componentHelper;
        $this->clientHelper = $clientHelper;
        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
    }

    /**
     * @param string $component
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $description
     *
     * @return void
     * @throws \Zend_Mail_Exception
     */
    public function send(
        string $component,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $description
    ): void {
        $attachments = [];
        $uploadedFiles = $this->phpEnvironmentRequest->getFiles()->toArray();

        if (!empty($uploadedFiles['files'])) {
            foreach ($uploadedFiles['files'] as $uploadFileInfo) {
                if ('' === $uploadFileInfo['name']) {
                    continue;
                }

                $attachment = new \Zend_Mime_Part(file_get_contents($uploadFileInfo['tmp_name']));
                $attachment->type = $uploadFileInfo['type'];
                $attachment->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
                $attachment->encoding = \Zend_Mime::ENCODING_BASE64;
                $attachment->filename = $uploadFileInfo['name'];

                $attachments[] = $attachment;
            }
        }

        $toEmail = $this->supportHelper->getContactEmail();
        $body = $this->createBody($component, $description);

        $this->sendMailNow($toEmail, $fromEmail, $fromName, $subject, $body, $attachments);
    }

    // ----------------------------------------

    /**
     * @param string $component
     * @param string $description
     *
     * @return string
     */
    private function createBody(
        string $component,
        string $description
    ): string {
        return <<<DATA

{$description}

-------------------------------- GENERAL -----------------------------------------
Component: {$this->componentHelper->getComponentTitle($component)}

-------------------------------- PLATFORM INFO -----------------------------------
Edition: {$this->magentoHelper->getEditionName()}
Version: {$this->magentoHelper->getVersion()}

-------------------------------- MODULE INFO -------------------------------------
Name: {$this->moduleHelper->getName()}
Version: {$this->moduleHelper->getPublicVersion()}

-------------------------------- LOCATION INFO -----------------------------------
Domain: {$this->clientHelper->getDomain()}
Ip: {$this->clientHelper->getIp()}

-------------------------------- PHP INFO ----------------------------------------
Version: {$this->clientHelper->getPhpVersion()}
Api: {$this->clientHelper->getPhpApiName()}
Memory Limit: {$this->clientHelper->getMemoryLimit()}
Max Execution Time: {$this->clientHelper->getExecutionTime()}
DATA;
    }

    /**
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $body
     * @param array $attachments
     *
     * @return void
     * @throws \Zend_Mail_Exception
     */
    private function sendMailNow(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $body,
        array $attachments = []
    ): void {
        $mail = new \Zend_Mail('UTF-8');

        $mail->addTo($toEmail)
             ->setFrom($fromEmail, $fromName)
             ->setSubject($subject)
             ->setBodyText($body, null, \Zend_Mime::ENCODING_8BIT);

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->send();
    }
}
