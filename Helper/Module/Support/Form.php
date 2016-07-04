<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Support;

class Form extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $urlBuilder;

    //########################################

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function send($component, $fromEmail, $fromName, $subject, $description, $severity)
    {
        $attachments = array();

        if (isset($_FILES['files'])) {
            foreach ($_FILES['files']['name'] as $key => $uploadFileName) {
                if ('' == $uploadFileName) {
                    continue;
                }

                $realName = $uploadFileName;
                $tempPath = $_FILES['files']['tmp_name'][$key];
                $mimeType = $_FILES['files']['type'][$key];

                $attachment = new \Zend_Mime_Part(file_get_contents($tempPath));
                $attachment->type        = $mimeType;
                $attachment->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
                $attachment->encoding    = \Zend_Mime::ENCODING_BASE64;
                $attachment->filename    = $realName;

                $attachments[] = $attachment;
            }
        }

        $toEmail = $this->getHelper('Module\Support')->getContactEmail();
        $componentTitle = $this->getHelper('Component')->getComponentTitle($component);
        $body = $this->createBody($subject,$componentTitle,$description,$severity);

        $this->sendMailNow($toEmail, $fromEmail, $fromName, $subject, $body, $attachments);
    }

    public function getSummaryInfo()
    {
        $locationInfo = array();
        $locationInfo['domain'] = $this->getHelper('Client')->getDomain();
        $locationInfo['ip'] = $this->getHelper('Client')->getIp();
        $locationInfo['directory'] = $this->getHelper('Client')->getBaseDirectory();

        $platformInfo = array();
        $platformInfo['name'] = $this->getHelper('Magento')->getName();
        $platformInfo['edition'] = $this->getHelper('Magento')->getEditionName();
        $platformInfo['version'] = $this->getHelper('Magento')->getVersion();
        $platformInfo['revision'] = $this->getHelper('Magento')->getRevision();

        $moduleInfo = array();
        $moduleInfo['name'] = $this->getHelper('Module')->getName();
        $moduleInfo['version'] = $this->getHelper('Module')->getVersion();
        $moduleInfo['revision'] = $this->getHelper('Module')->getRevision();

        $phpInfo = $this->getHelper('Client')->getPhpSettings();
        $phpInfo['api'] = $this->getHelper('Client')->getPhpApiName();
        $phpInfo['version'] = $this->getHelper('Client')->getPhpVersion();

        $mysqlInfo = $this->getHelper('Client')->getMysqlSettings();
        $mysqlInfo['api'] = $this->getHelper('Client')->getMysqlApiName();
        $prefix = $this->getHelper('Magento')->getDatabaseTablesPrefix();
        $mysqlInfo['prefix'] = $prefix != '' ? $prefix : 'Disabled';
        $mysqlInfo['version'] = $this->getHelper('Client')->getMysqlVersion();
        $mysqlInfo['database'] = $this->getHelper('Magento')->getDatabaseName();

        $additionalInfo = array();
        $additionalInfo['system'] = $this->getHelper('Client')->getSystem();
        $additionalInfo['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A';
        $additionalInfo['admin'] = $this->urlBuilder->getUrl('adminhtml');
        $additionalInfo['license_key'] = $this->getHelper('Module\License')->getKey();
        $additionalInfo['installation_key'] = $this->getHelper('Module')->getInstallationKey();

        $info = <<<DATA
-------------------------------- PLATFORM INFO -----------------------------------
Name: {$platformInfo['name']}
Edition: {$platformInfo['edition']}
Version: {$platformInfo['version']}
Revision: {$platformInfo['revision']}

-------------------------------- MODULE INFO -------------------------------------
Name: {$moduleInfo['name']}
Version: {$moduleInfo['version']}
Revision: {$moduleInfo['revision']}

-------------------------------- LOCATION INFO -----------------------------------
Domain: {$locationInfo['domain']}
Ip: {$locationInfo['ip']}
Directory: {$locationInfo['directory']}

-------------------------------- PHP INFO ----------------------------------------
Version: {$phpInfo['version']}
Api: {$phpInfo['api']}
Memory Limit: {$phpInfo['memory_limit']}
Max Execution Time: {$phpInfo['max_execution_time']}

-------------------------------- MYSQL INFO --------------------------------------
Version: {$mysqlInfo['version']}
Api: {$mysqlInfo['api']}
Database: {$mysqlInfo['database']}
Tables Prefix: {$mysqlInfo['prefix']}
Connection Timeout: {$mysqlInfo['connect_timeout']}
Wait Timeout: {$mysqlInfo['wait_timeout']}

------------------------------ ADDITIONAL INFO -----------------------------------
System Name: {$additionalInfo['system']}
User Agent: {$additionalInfo['user_agent']}
License Key: {$additionalInfo['license_key']}
Installation Key: {$additionalInfo['installation_key']}
Admin Panel: {$additionalInfo['admin']}
DATA;

        return $info;
    }

    //########################################

    private function createBody($subject, $component, $description, $severity)
    {
        $currentDate = $this->getHelper('Data')->getCurrentGmtDate();

        $body = <<<DATA

{$description}

-------------------------------- GENERAL -----------------------------------------
Date: {$currentDate}
Component: {$component}
Subject: {$subject}
%severity%

DATA;

        $severity = $severity ? "Severity: {$severity}" : '';
        $body = str_replace('%severity%', $severity, $body);

        $body .= $this->getSummaryInfo();

        return $body;
    }

    private function sendMailNow($toEmail, $fromEmail, $fromName, $subject, $body, array $attachments = array())
    {
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

    //########################################
}