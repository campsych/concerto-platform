<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestSessionRepository")
 */
class TestSession extends AEntity {

    const STATUS_RUNNING = 0;
    const STATUS_SERIALIZED = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="sessions")
     */
    private $test;

    /**
     *
     * @ORM\Column(type="string")
     */
    private $panelNodeId;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $panelNodePort;

    /**
     *
     * @ORM\Column(type="string")
     */
    private $testNodeId;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $testNodePort;

    /**
     *
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="ViewTemplate")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $template;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $templateCss;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $templateJs;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $templateHtml;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $templateHead;

    /**
     * @ORM\ManyToOne(targetEntity="ViewTemplate")
     * @ORM\JoinColumn(name="loader_id", referencedColumnName="id", nullable=true)
     */
    private $loader;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $loaderCss;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $loaderJs;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $loaderHtml;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $loaderHead;

    /**
     *
     * @ORM\Column(type="integer")
     */
    private $timeLimit;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $params;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $templateParams;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $returns;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $clientIp;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $clientBrowser;

    /**
     *
     * @ORM\Column(type="text", nullable=true, length=40)
     */
    private $hash;

    /**
     *
     * @ORM\Column(type="boolean")
     */
    private $debug;

    public function __construct() {
        parent::__construct();

        $this->hash = sha1(rand(1000, 9999));
        $this->status = self::STATUS_RUNNING;
        $this->timeLimit = 0;
        $this->finalize = 0;
    }

    public function getOwner() {
        return $this->getTest()->getOwner();
    }

    /**
     * Set panel node id
     *
     * @param string $panelNodeId
     * @return TestSession
     */
    public function setPanelNodeId($panelNodeId) {
        $this->panelNodeId = $panelNodeId;

        return $this;
    }

    /**
     * Get panel node id
     *
     * @return string 
     */
    public function getPanelNodeId() {
        return $this->panelNodeId;
    }

    /**
     * Set panel node port
     *
     * @param integer $panelNodePort
     * @return TestSession
     */
    public function setPanelNodePort($panelNodePort) {
        $this->panelNodePort = $panelNodePort;

        return $this;
    }

    /**
     * Get panel node port
     *
     * @return integer 
     */
    public function getPanelNodePort() {
        return $this->panelNodePort;
    }

    /**
     * Set test node id
     *
     * @param string $testNodeId
     * @return TestSession
     */
    public function setTestNodeId($testNodeId) {
        $this->testNodeId = $testNodeId;

        return $this;
    }

    /**
     * Get test node id
     *
     * @return string 
     */
    public function getTestNodeId() {
        return $this->testNodeId;
    }

    /**
     * Set test node port
     *
     * @param integer $testNodePort
     * @return TestSession
     */
    public function setTestNodePort($testNodePort) {
        $this->testNodePort = $testNodePort;

        return $this;
    }

    /**
     * Get test node port
     *
     * @return integer 
     */
    public function getTestNodePort() {
        return $this->testNodePort;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TestSession
     */
    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set template CSS
     *
     * @param string $css
     * @return TestSession
     */
    public function setTemplateCss($css) {
        $this->templateCss = $css;

        return $this;
    }

    /**
     * Get template CSS
     *
     * @return string 
     */
    public function getTemplateCss() {
        return $this->templateCss;
    }

    /**
     * Set template JS
     *
     * @param string $js
     * @return TestSession
     */
    public function setTemplateJs($js) {
        $this->templateJs = $js;

        return $this;
    }

    /**
     * Get template JS
     *
     * @return string 
     */
    public function getTemplateJs() {
        return $this->templateJs;
    }

    /**
     * Set templateHtml
     *
     * @param string $templateHtml
     * @return TestSession
     */
    public function setTemplateHtml($templateHtml) {
        $this->templateHtml = $templateHtml;

        return $this;
    }

    /**
     * Get templateHtml
     *
     * @return string 
     */
    public function getTemplateHtml() {
        return $this->templateHtml;
    }

    /**
     * Set templateHead
     *
     * @param string $templateHead
     * @return TestSession
     */
    public function setTemplateHead($templateHead) {
        $this->templateHead = $templateHead;

        return $this;
    }

    /**
     * Get templateHead
     *
     * @return string 
     */
    public function getTemplateHead() {
        return $this->templateHead;
    }

    /**
     * Set loader CSS
     *
     * @param string $css
     * @return TestSession
     */
    public function setLoaderCss($css) {
        $this->loaderCss = $css;

        return $this;
    }

    /**
     * Get loader CSS
     *
     * @return string 
     */
    public function getLoaderCss() {
        return $this->loaderCss;
    }

    /**
     * Set loader JS
     *
     * @param string $js
     * @return TestSession
     */
    public function setLoaderJs($js) {
        $this->loaderJs = $js;

        return $this;
    }

    /**
     * Get loader JS
     *
     * @return string 
     */
    public function getLoaderJs() {
        return $this->loaderJs;
    }

    /**
     * Set loaderHtml
     *
     * @param string $loaderHtml
     * @return TestSession
     */
    public function setLoaderHtml($loaderHtml) {
        $this->loaderHtml = $loaderHtml;

        return $this;
    }

    /**
     * Get loaderHtml
     *
     * @return string 
     */
    public function getLoaderHtml() {
        return $this->loaderHtml;
    }

    /**
     * Set loaderHead
     *
     * @param string $loaderHead
     * @return TestSession
     */
    public function setLoaderHead($loaderHead) {
        $this->loaderHead = $loaderHead;

        return $this;
    }

    /**
     * Get loaderHead
     *
     * @return string 
     */
    public function getLoaderHead() {
        return $this->loaderHead;
    }

    /**
     * Set test
     *
     * @param Test $test
     * @return TestSession
     */
    public function setTest(Test $test = null) {
        $this->test = $test;

        return $this;
    }

    /**
     * Get test
     *
     * @return Test 
     */
    public function getTest() {
        return $this->test;
    }

    /**
     * Set template
     *
     * @param ViewTemplate $template
     * @return TestSession
     */
    public function setTemplate(ViewTemplate $template = null) {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return ViewTemplate 
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * Set loader
     *
     * @param ViewTemplate $loader
     * @return TestSession
     */
    public function setLoader(ViewTemplate $loader = null) {
        $this->loader = $loader;

        return $this;
    }

    /**
     * Get loader
     *
     * @return ViewTemplate 
     */
    public function getLoader() {
        return $this->loader;
    }

    /**
     * Set time limit
     *
     * @param integer $timeLimit
     * @return TestSession
     */
    public function setTimeLimit($timeLimit) {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    /**
     * Get time limit (in seconds)
     *
     * @return integer 
     */
    public function getTimeLimit() {
        return $this->timeLimit;
    }

    /**
     * Set params
     *
     * @param string $params
     * @return TestSession
     */
    public function setParams($params) {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return string 
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Set template params
     *
     * @param string $params
     * @return TestSession
     */
    public function setTemplateParams($params) {
        $this->templateParams = $params;

        return $this;
    }

    /**
     * Get template eparams
     *
     * @return string 
     */
    public function getTemplateParams() {
        return $this->templateParams;
    }

    /**
     * Set returns
     *
     * @param string $returns
     * @return TestSession
     */
    public function setReturns($returns) {
        $this->returns = $returns;

        return $this;
    }

    /**
     * Get returns
     *
     * @return string 
     */
    public function getReturns() {
        return $this->returns;
    }

    /**
     * Set error
     *
     * @param string $error
     * @return TestSession
     */
    public function setError($error) {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string 
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Set client ip
     *
     * @param string $clientIp
     * @return TestSession
     */
    public function setClientIp($clientIp) {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * Get client ip
     *
     * @return string 
     */
    public function getClientIp() {
        return $this->clientIp;
    }

    /**
     * Set client browser
     *
     * @param string $clientBrowser
     * @return TestSession
     */
    public function setClientBrowser($clientBrowser) {
        $this->clientBrowser = $clientBrowser;

        return $this;
    }

    /**
     * Get client browser
     *
     * @return string 
     */
    public function getClientBrowser() {
        return $this->clientBrowser;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return TestSession
     */
    public function setHash($hash) {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Set debug
     *
     * @param boolean $debug
     * @return TestSession
     */
    public function setDebug($debug) {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug
     *
     * @return boolean 
     */
    public function isDebug() {
        return $this->debug;
    }

}
