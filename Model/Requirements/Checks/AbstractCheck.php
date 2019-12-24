<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements\Checks;

/**
 * Class \Ess\M2ePro\Model\Requirements\Checks\AbstractCheck
 */
abstract class AbstractCheck extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Requirements\Reader */
    protected $requirementsReader;

    /** @var \Composer\Semver\VersionParser */
    protected $versionParser;

    /** @var \Ess\M2ePro\Model\Requirements\Renderer\AbstractRenderer */
    protected $renderer;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Requirements\Reader $requirementsReader,
        \Composer\Semver\VersionParser $versionParser,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->requirementsReader = $requirementsReader;
        $this->versionParser      = $versionParser;
    }

    //########################################

    abstract public function isMeet();
    abstract public function getMin();
    abstract public function getReal();

    //########################################

    public function getReader()
    {
        return $this->requirementsReader;
    }

    public function getRenderer()
    {
        return $this->renderer;
    }

    public function getVersionParser()
    {
        return $this->versionParser;
    }

    // ---------------------------------------

    public function setRenderer(\Ess\M2ePro\Model\Requirements\Renderer\AbstractRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    //########################################
}
