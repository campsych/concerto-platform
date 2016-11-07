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
    private $testServerNodeId;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $testServerNodePort;

    /**
     *
     * @ORM\Column(type="string")
     */
    private $rServerNodeId;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rServerNodePort;

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
     * Set test server node id
     *
     * @param string $testServerNodeId
     * @return TestSession
     */
    public function setTestServerNodeId($testServerNodeId) {
        $this->testServerNodeId = $testServerNodeId;

        return $this;
    }

    /**
     * Get test server node if
     *
     * @return string 
     */
    public function getTestServerNodeId() {
        return $this->testServerNodeId;
    }

    /**
     * Set test server node port
     *
     * @param integer $testServerNodePort
     * @return TestSession
     */
    public function setTestServerNodePort($testServerNodePort) {
        $this->testServerNodePort = $testServerNodePort;

        return $this;
    }

    /**
     * Get test server node port
     *
     * @return integer 
     */
    public function getTestServerNodePort() {
        return $this->testServerNodePort;
    }

    /**
     * Set R server node id
     *
     * @param string $rServerNodeId
     * @return TestSession
     */
    public function setRServerNodeId($rServerNodeId) {
        $this->rServerNodeId = $rServerNodeId;

        return $this;
    }

    /**
     * Get R server node id
     *
     * @return string 
     */
    public function getRServerNodeId() {
        return $this->rServerNodeId;
    }

    /**
     * Set R server node port
     *
     * @param integer $rServerNodePort
     * @return TestSession
     */
    public function setRServerNodePort($rServerNodePort) {
        $this->rServerNodePort = $rServerNodePort;

        return $this;
    }

    /**
     * Get R server node port
     *
     * @return integer 
     */
    public function getRServerNodePort() {
        return $this->rServerNodePort;
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
