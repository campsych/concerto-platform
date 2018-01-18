<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\TestBundle\Service\TestSessionCountService;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AdministrationController
{

    private $templating;
    private $service;
    private $sessionCountService;

    public function __construct(EngineInterface $templating, AdministrationService $service, TestSessionCountService $sessionCountService)
    {
        $this->templating = $templating;
        $this->service = $service;
        $this->sessionCountService = $sessionCountService;
    }

    public function settingsMapAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => array(
                "exposed" => $this->service->getExposedSettingsMap(),
                "internal" => $this->service->getInternalSettingsMap(true)
            )
        ));
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function messagesCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getMessagesCollection()
        ));
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function updateSettingsMapAction(Request $request)
    {
        $this->service->setSettings(json_decode($request->get("map")), true);
        $result = array("result" => 0);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function sessionCountCollectionAction($filter)
    {
        $collection = $this->sessionCountService->getCollection(json_decode($filter, true));
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $collection
        ));
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function clearSessionCountAction()
    {
        $this->sessionCountService->clear();
        $result = array("result" => 0);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function deleteMessageAction($object_ids)
    {
        $result = $this->service->deleteMessage($object_ids);
        $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function clearMessagesAction()
    {
        $this->service->clearMessages();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function tasksCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getTasksCollection()
        ));
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function taskBackupAction()
    {
        $return = $this->service->scheduleBackupTask($out, true);
        $response = new Response(json_encode(array("result" => $return, "out" => $out)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function taskRestoreAction()
    {
        $return = $this->service->scheduleRestoreTask($out, true);
        $response = new Response(json_encode(array("result" => $return, "out" => $out)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function taskContentUpgradeAction(Request $request)
    {
        $backup = $request->get("backup");
        $return = $this->service->scheduleContentUpgradeTask($out, $backup, true);
        $response = new Response(json_encode(array("result" => $return, "out" => $out)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function taskPlatformUpgradeAction(Request $request)
    {
        $backup = $request->get("backup");
        $return = $this->service->schedulePlatformUpgradeTask($out, $backup, true);
        $response = new Response(json_encode(array("result" => $return, "out" => $out)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function taskPackageInstallAction(Request $request)
    {
        $install_options = json_decode($request->get("install_options"), true);
        $return = $this->service->schedulePackageInstallTask($out, $install_options, true);
        $response = new Response(json_encode(array("result" => $return, "out" => $out)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function apiClientCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getApiClientsCollection()
        ));
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function deleteApiClientAction($object_ids)
    {
        $result = $this->service->deleteApiClient($object_ids);
        $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function clearApiClientAction()
    {
        $this->service->clearApiClients();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function addApiClientAction()
    {
        $this->service->addApiClient();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function packagesStatusAction()
    {
        $return_var = $this->service->packageStatus($output);
        $response = new Response(json_encode(array("result" => $return_var ? 0 : 1, "output" => $output)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
