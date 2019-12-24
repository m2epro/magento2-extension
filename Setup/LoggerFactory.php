<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class \Ess\M2ePro\Setup\LoggerFactory
 */
class LoggerFactory
{
    const LOGFILE_NAME = 'setup-error.log';

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $_objectManager;

    /** @var \Magento\Framework\App\Filesystem\DirectoryList $directoryList */
    private $directoryList;

    //########################################

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->_objectManager = $objectManager;
        $this->directoryList  = $directoryList;
    }

    //########################################

    public function create(
        $channelName = 'm2epro-setup-log',
        $fileName = self::LOGFILE_NAME,
        array $data = []
    ) {
        $logFilePath = $this->directoryList->getPath(DirectoryList::LOG) .DIRECTORY_SEPARATOR.
                       'm2epro' .DIRECTORY_SEPARATOR. $fileName;

        $streamHandler = new \Monolog\Handler\StreamHandler($logFilePath);
        $streamHandler->setFormatter(new \Monolog\Formatter\LineFormatter());

        $logger = $this->_objectManager->create(
            \Magento\Framework\Logger\Monolog::class,
            [
                'name'     => $channelName,
                'handlers' => [$streamHandler]
            ]
        );

        return $logger;
    }

    //########################################
}
