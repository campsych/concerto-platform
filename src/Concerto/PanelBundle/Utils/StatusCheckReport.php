<?php

namespace Concerto\PanelBundle\Utils;

interface StatusCheckReport {

    public function getErrorsString();

    public function isOk();
}
