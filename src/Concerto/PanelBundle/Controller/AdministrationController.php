<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Service\FileService;
use Concerto\PanelBundle\Service\GitService;
use Concerto\PanelBundle\Service\ImportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\TestBundle\Service\TestSessionCountService;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin")
 */
class AdministrationController
{
    private $templating;
    private $service;
    private $sessionCountService;
    private $fileService;
    private $gitService;
    private $translator;
    private $importService;

    public function __construct(EngineInterface $templating, AdministrationService $service, TestSessionCountService $sessionCountService, FileService $fileService, GitService $gitService, TranslatorInterface $translator, ImportService $importService)
    {
        $this->templating = $templating;
        $this->service = $service;
        $this->sessionCountService = $sessionCountService;
        $this->fileService = $fileService;
        $this->gitService = $gitService;
        $this->translator = $translator;
        $this->importService = $importService;
    }

    /**
     * @Route("/AdministrationSetting/map", name="AdministrationSetting_map")
     * @return Response
     */
    public function settingsMapAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => array(
                "exposed" => $this->service->getExposedSettingsMap(),
                "internal" => $this->service->getInternalSettingsMap()
            )
        ));
    }

    /**
     * @Route("/Administration/Messages/collection", name="Administration_messages_collection")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function messagesCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getMessagesCollection()
        ));
    }

    /**
     * @Route("/AdministrationSetting/map/update", name="AdministrationSetting_map_update", methods={"POST"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
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
     * @Route("/Administration/SessionCount/{filter}/collection", name="Administration_session_count_collection", defaults={"filter"="{}"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param string $filter
     * @return Response
     */
    public function sessionCountCollectionAction($filter)
    {
        $collection = $this->sessionCountService->getCollection(json_decode($filter, true));
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $collection
        ));
    }

    /**
     * @Route("/AdministrationSetting/SessionCount/clear", name="AdministrationSetting_session_count_clear", methods={"POST"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
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
     * @Route("/Administration/Messages/{object_ids}/delete", name="Administration_messages_delete")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param string $object_ids
     * @return Response
     */
    public function deleteMessageAction($object_ids)
    {
        $this->service->deleteMessage($object_ids);
        $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/Messages/clear", name="Administration_messages_clear")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function clearMessagesAction()
    {
        $this->service->clearMessages();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/collection", name="Administration_tasks_collection")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function tasksCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getTasksCollection()
        ));
    }

    /**
     * @Route("/Administration/ScheduledTask/package_install", name="Administration_tasks_package_install")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
     */
    public function taskPackageInstallAction(Request $request)
    {
        $installOptions = json_decode($request->get("install_options"), true);
        $success = $this->service->scheduleTaskPackageInstall($installOptions, $output, $errors);
        $content = array(
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $this->trans($errors)
        );

        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/git_pull", name="Administration_tasks_git_pull")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function taskGitPullAction(Request $request)
    {
        $exportInstructions = $request->get("exportInstructions");
        $success = $this->gitService->scheduleTaskGitPull($exportInstructions, $output, $errors);
        $content = array(
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $this->trans($errors)
        );

        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/git_enable", name="Administration_tasks_git_enable")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function taskGitEnableAction(Request $request)
    {
        $instructions = $request->get("instructions");
        $success = $this->gitService->scheduleTaskGitEnable(
            $request->get("url"),
            $request->get("branch"),
            $request->get("login"),
            $request->get("password"),
            $instructions,
            false,
            $output,
            $errors
        );
        $content = array(
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $this->trans($errors)
        );

        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/git_update", name="Administration_tasks_git_update")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function taskGitUpdateAction(Request $request)
    {
        $exportInstructions = $request->get("exportInstructions");
        $success = $this->gitService->scheduleTaskGitUpdate($exportInstructions, $output, $errors);
        $content = array(
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $this->trans($errors)
        );

        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/git_reset", name="Administration_tasks_git_reset")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function taskGitResetAction(Request $request)
    {
        $exportInstructions = $request->get("exportInstructions");
        $success = $this->gitService->scheduleTaskGitReset($exportInstructions, $output, $errors);
        $content = array(
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $this->trans($errors)
        );

        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/api_clients/collection", name="Administration_api_clients_collection")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function apiClientCollectionAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getApiClientsCollection()
        ));
    }

    /**
     * @Route("/Administration/api_clients/{object_ids}/delete", name="Administration_api_clients_delete")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param string $object_ids
     * @return Response
     */
    public function deleteApiClientAction($object_ids)
    {
        $this->service->deleteApiClient($object_ids);
        $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/api_clients/clear", name="Administration_api_clients_clear")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function clearApiClientAction()
    {
        $this->service->clearApiClients();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/api_clients/add", name="Administration_api_clients_add")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function addApiClientAction()
    {
        $this->service->addApiClient();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/packages/status", name="Administration_packages_status")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function packagesStatusAction()
    {
        $return_var = $this->service->packageStatus($output);
        $response = new Response(json_encode(array("result" => $return_var ? 0 : 1, "output" => $output)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/ScheduledTask/content_import", name="Administration_tasks_content_import")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
     */
    public function taskContentImportAction(Request $request)
    {
        $file = $request->get("file");
        if ($file) {
            $file = realpath($this->fileService->getPrivateUploadDirectory()) . "/" . $this->fileService->canonicalizePath(basename($file));
        }
        $url = $request->get("url");
        $input = $file ? $file : $url;
        if ($input === null) $input = $this->service->getSettingValue("content_url");

        $instructions = $request->get("instructions");
        if ($instructions === null) $instructions = $this->service->getContentTransferOptions();

        $success = $this->importService->scheduleTaskImportContent($input, $instructions, true, $output, $errors);
        $response = new Response(json_encode([
            "result" => $success ? 0 : 1,
            "output" => $output,
            "errors" => $success ? null : $this->trans($errors)
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/content/export/{instructions}", name="Administration_content_export", defaults={"instructions"="[]"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param string $instructions
     * @return Response
     */
    public function exportContentAction($instructions = "[]")
    {
        $returnCode = $this->service->exportContent($instructions, $zipPath, $output);
        if ($returnCode === 0) {
            $response = new Response(file_get_contents($zipPath));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment; filename="export.concerto.zip"');
            return $response;
        } else {
            $response = new Response($output, 500);
            return $response;
        }
    }

    /**
     * @Route("/Administration/user", name="Administration_user")
     * @return Response
     */
    public function getAuthUserAction()
    {
        $user = $this->service->getAuthorizedUser();

        $content = array("user" => null);
        if ($user) {
            $content = array(
                "user" => array(
                    "id" => $user->getId(),
                    "username" => $user->getUsername(),
                    "role_super_admin" => $user->hasRoleName(User::ROLE_SUPER_ADMIN) ? 1 : 0,
                    "role_test" => $user->hasRoleName(User::ROLE_TEST) ? 1 : 0,
                    "role_template" => $user->hasRoleName(User::ROLE_TEMPLATE) ? 1 : 0,
                    "role_table" => $user->hasRoleName(User::ROLE_TABLE) ? 1 : 0,
                    "role_file" => $user->hasRoleName(User::ROLE_FILE) ? 1 : 0,
                    "role_wizard" => $user->hasRoleName(User::ROLE_WIZARD) ? 1 : 0
                )
            );
        }
        $response = new Response(json_encode($content));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/git/disable", name="Administration_git_disable")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
     */
    public function disableGitAction(Request $request)
    {
        $success = $this->gitService->disableGit();

        $response = new Response(json_encode(array("result" => $success ? 0 : 1)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/git/status", name="Administration_git_status")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function gitStatusAction(Request $request)
    {
        $status = $this->gitService->getStatus($errorMessages);
        $responseContent = [
            "result" => $status === false ? 1 : 0,
            "status" => $status === false ? null : $status,
            "errors" => $status === false ? $this->trans($errorMessages) : null
        ];

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/git/diff/{sha}", name="Administration_git_diff")
     * @param string|null $sha
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function gitDiffAction($sha)
    {
        $diff = $this->gitService->getDiff($sha, $errorMessages);
        $responseContent = [
            "result" => $diff === false ? 1 : 0,
            "diff" => $diff === false ? null : $diff,
            "errors" => $diff === false ? $this->trans($errorMessages) : null
        ];

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/git/commit", name="Administration_git_commit")
     * @param Request $request
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function gitCommitAction(Request $request)
    {
        $commit = $this->gitService->commit(
            $request->get("message"),
            $output,
            $errorMessages
        );
        $responseContent = [
            "result" => $commit === false ? 1 : 0,
            "output" => $output,
            "errors" => $commit === false ? $this->trans($errorMessages) : null
        ];

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Administration/git/push", name="Administration_git_push")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @return Response
     */
    public function gitPushAction()
    {
        $push = $this->gitService->push(
            $output,
            $errorMessages
        );

        $responseContent = [
            "result" => $push === false ? 1 : 0,
            "output" => $output,
            "errors" => $push === false ? $this->trans($errorMessages) : null
        ];

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    protected function trans($messages, $domain = null)
    {
        if (!$messages) return $messages;
        if (is_array($messages)) {
            foreach ($messages as &$message) {
                $message = $this->translator->trans($message, [], $domain);
            }
            return $messages;
        }
        return $this->translator->trans($messages, [], $domain);
    }
}
