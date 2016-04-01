<?php

namespace Concerto\PanelBundle\Utils;

use Concerto\PanelBundle\Utils\StatusCheckReport;

class SystemExecutableStatus implements StatusCheckReport {

    const RETURN_OK = 0;

    protected $return_code;
    protected $documentation_link = null;
    protected $name;
    protected $command;
    protected $detected_version = null;
    protected $minimal_required_version = null;

    public function __construct($name, $command) {
        $this->name = $name;
        $this->command = $command;
    }

    public function setReturnCode($return_code) {
        $this->return_code = (int) $return_code;
        // safety for non-integer return_code, which would incorrectly be treated as OK
        if ((string) $this->return_code != (string) $return_code)
            $this->return_code = -999;
    }

    public function getReturnCode() {
        return $this->return_code;
    }

    public function setDocumentationLink($link) {
        $this->documentation_link = $link;
    }

    public function setDetectedVersion($version_string) {
        $this->detected_version = $version_string;
    }

    public function setMinimalRequiredVersion($version_string) {
        $this->minimal_required_version = $version_string;
    }

    public function getErrorsString() {
        $errors = $this->getErrors();
        if (( count($errors) > 0 ) && (!empty($this->documentation_link) ))
            $errors[] = "Check {$this->documentation_link} for more information about installation and configuration of {$this->name}.";

        return join(PHP_EOL . PHP_EOL, $errors);
    }

    public function isOk() {
        return count($this->getErrors()) === 0;
    }

    protected function isVersionOk() {
        // nothing in particular is required, so it's ok
        if (empty($this->minimal_required_version))
            return true;

        // if something is required, but nothing was detected, we return error
        if (empty($this->detected_version))
            return false;

        // version_compare returns -1 if the first version is lower than the second one
        return -1 < version_compare($this->detected_version, $this->minimal_required_version);
    }

    protected function getErrors() {
        $errors = array();

        if ($this->return_code != self::RETURN_OK)
            $errors[] = "Command {$this->command} returned error code {$this->return_code} when executed. Please make sure that it's installed, added to your system path, and working correctly.";

        // no point to distract the user about version, if execution returned error
        if (empty($erros) && !$this->isVersionOk()
        ) {
            if (is_null($this->detected_version))
                $errors[] = "Concerto was unable to detect version of required {$this->command} executable. Please verify that its installation and configuration are correct.";
            else
                $errors[] = "Executable {$this->command} is installed in version {$this->detected_version}, but concerto needs at least version {$this->minimal_required_version} to work properly. Please update it and try again.";
        }
        return $errors;
    }

}
