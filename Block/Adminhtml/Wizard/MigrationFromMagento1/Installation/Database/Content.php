<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;
use Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\UnexpectedlyCopied;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database\Content
 */
class Content extends AbstractBlock
{
    protected $_template = 'wizard/migrationFromMagento1/installation/database.phtml';

    /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\UnexpectedlyCopied */
    protected $preconditionsChecker;

    /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader */
    protected $mappingTablesDownloader;

    /** @var \Magento\Framework\View\Element\Messages $messagesBlock */
    protected $messagesBlock;

    /** @var array */
    protected $messages = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\UnexpectedlyCopied $preconditionsChecker,
        \Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader $mappingTablesDownloader,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->preconditionsChecker    = $preconditionsChecker;
        $this->mappingTablesDownloader = $mappingTablesDownloader;
    }

    //########################################

    protected function _beforeToHtml()
    {
        if ($this->getCurrentWizardStatus() !== MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED) {
            return parent::_beforeToHtml();
        }

        $this->messagesBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Messages');
        $this->messages = ['M2E Pro database tables were transferred from Magento v1.x to Magento v2.x.'];
        $this->getHelper('Data\Session')->removeValue('unexpected_migration_m1_url');

        try {
            $this->preconditionsChecker->checkPreconditions();
            $this->messages[] = 'Click <b>Continue</b> to complete the migration process.';
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            if (!$this->recognizeLogicException($e)) {

                $this->processUnexpectedException($e);
                return parent::_beforeToHtml();
            }
        } catch (\Exception $e) {
            $this->processUnexpectedException($e);
            return parent::_beforeToHtml();
        }

        $this->messages[] =
            '<br><br><b>Note</b>: The step is rather time-consuming. If you are logged out from Magento, log in again 
            and go to 
            <i>Stores > Settings > Configuration > M2E Pro > Advanced Settings > Migration from Magento v1.x</i>.';

        $this->messagesBlock->addWarning($this->__(implode(' ', $this->messages)));
        $this->setChild('unexpectedly_copied_from_m1_message', $this->messagesBlock);

        return parent::_beforeToHtml();
    }

    /**
     * @param \Ess\M2ePro\Model\Exception\Logic $exception
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function recognizeLogicException(\Ess\M2ePro\Model\Exception\Logic $exception)
    {
        switch ($exception->getCode()) {
            case UnexpectedlyCopied::EXCEPTION_CODE_WRONG_VERSION:
                $this->messages[] = sprintf(
                    '<br><br>Your current version of M2E Pro does not support the migration to Magento v2.x.
                    Please follow <a href="%s" target="_blank">these instructions</a> to get the required Module 
                    version and complete the migration process.',
                    $this->helperFactory->getObject('Module_Support')->getKnowledgebaseArticleUrl('1600682')
                );
                return true;

            case UnexpectedlyCopied::EXCEPTION_CODE_MAPPING_VIOLATED:
                $this->messages[] = '<br><br>Some required M2E Pro data is missing.';

                if (!$this->tryResolveM1Url()) {
                    $this->messages[] =
                        'To upload it, the Migration Tool requires an exact location of your Magento website. 
                        Please type the URL address of your Magento v1.x below.';
                } else {
                    $this->messages[] = 'The Migration Tool will try to upload it automatically.';
                }

                $this->messages[] =
                    'Choose whether to stop automatic synchronization on Magento v1.x, then click <b>Continue</b>.';

                $form = $this->createBlock('Wizard_MigrationFromMagento1_Installation_Database_Content_Form');
                $this->setChild('magento1_url_form', $form);
                return true;

            case UnexpectedlyCopied::EXCEPTION_CODE_TABLES_DO_NOT_EXIST:
                $this->messages = [
                    'M2E Pro tables dump from Magento v1.x was not imported to the Magneto v2.x database. 
                    Please complete the action, then click <b>Continue</b>.'
                ];
                return true;

            default:
                return false;
        }
    }

    /**
     * @param \Exception $e
     * @param \Magento\Framework\View\Element\Messages $messagesBlock
     */
    private function processUnexpectedException(\Exception $e)
    {
        $this->getHelper('Module\Exception')->process($e);

        $this->messagesBlock->addError(
            $this->__('Checking migration preconditions failed. Reason: %reason%', $e->getMessage())
        );
        $this->setChild('unexpectedly_copied_from_m1_message', $this->messagesBlock);
    }

    /**
     * @return bool
     */
    private function tryResolveM1Url()
    {
        try {
            /** @var MigrationFromMagento1 $wizard */
            $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);

            $m1BaseUrl = $this->mappingTablesDownloader->resolveM1Endpoint($wizard->getPossibleM1Domain());
            $this->getHelper('Data\Session')->setValue('unexpected_migration_m1_url', $m1BaseUrl);
        } catch (\Ess\M2ePro\Model\Exception\Logic $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCurrentWizardStatus()
    {
        /** @var MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        return $wizard->getCurrentStatus();
    }

    //########################################
}
