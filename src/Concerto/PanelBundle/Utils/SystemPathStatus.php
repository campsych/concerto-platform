<?php

namespace Concerto\PanelBundle\Utils;

use Concerto\PanelBundle\Utils\StatusCheckReport;

class SystemPathStatus implements StatusCheckReport {

    protected $name;
    protected $path;
    protected $present;
    protected $required_type;
    protected $detected_type;
    protected $required_access;
    protected $detected_access = array();
    protected $additional_error;
    protected $fixed = false;

    public function __construct($name, $path) {
        $this->name = $name;
        $this->path = $path;
    }

    public function setPresent($present) {
        $this->present = $present;
    }

    public function setRequiredType($type) {
        $this->required_type = $type;
    }

    public function setDetectedType($type) {
        $this->detected_type = $type;
    }

    public function setRequiredAccess($access) {
        $this->required_access = (array) $access;
    }

    public function setDetectedAccess($access, $state) {
        $this->detected_access[$access] = $state;
    }

    public function setAdditionalErrorMessage($msg) {
        $this->additional_error = $msg;
    }

    public function getErrorsString() {
        $errors = $this->getErrors();
        if (( count($errors) > 0 ) && (!empty($this->documentation_link) ))
            $errors[] = $this->additional_error;

        return join(PHP_EOL . PHP_EOL, $errors);
    }

    public function isOk() {
        return count($this->getErrors()) === 0;
    }

    public function setFixed($fixed) {
        $this->fixed = $fixed;
    }

    protected function getErrors() {
        $errors = array();

        if (!$this->present)
            $errors[] = "Path {$this->path} needed by {$this->name} is not present in your system, please verify it.";
        else { // no reason to check it, if the path doesn't exist at all...
            if (!empty($this->required_type)) {
                if (is_array($this->required_type)) {
                    if (!in_array($this->detected_type, $this->required_type)) {
                        $tmp = $this->required_type;
                        $last = array_pop($tmp);
                        $errors[] = "Path {$this->path} needed by {$this->name} is a {$this->detected_type}, but should be either a " . join(', ', $tmp) . " or a $last.";
                    }
                } else
                if ($this->required_type != $this->detected_type)
                    $errors[] = "Path {$this->path} needed by {$this->name} is a {$this->detected_type}, but should be a {$this->required_type}.";
            }
        }

        if (!empty($this->required_access))
            foreach ($this->required_access as $right) {
                if (empty($this->detected_access[$right]))
                    $errors[] = "Path {$this->path} needed by {$this->name} needs to be {$right}able, but it isn't.";
            }

        return $errors;
    }

    public function wasFixed() {
        return $this->fixed;
    }

}
