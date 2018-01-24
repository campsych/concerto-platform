<?php

namespace Concerto\PanelBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\UserService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class UserController extends ASectionController
{

    const ENTITY_NAME = "User";

    public function __construct(EngineInterface $templating, UserService $service, TranslatorInterface $translator, TokenStorageInterface $securityTokenStorage)
    {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
    }

    /**
     * @Route("/User/fetch/{object_id}/{format}", name="User_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/User/collection/{format}", name="User_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/User/form/{action}", name="User_form", defaults={"action":"edit"})
     * @param string $action
     * @param array $params
     * @return Response
     */
    public function formAction($action = "edit", $params = array())
    {
        return parent::formAction($action, $params);
    }

    /**
     * @Route("/User/{object_id}/save", name="User_save")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $request->get("accessibility"),
            $request->get("archived") === "1",
            $this->service->get($request->get("owner")),
            $request->get("groups"),
            $request->get("email"),
            $request->get("username"),
            $request->get("password"),
            $request->get("passwordConfirmation"),
            $request->get("role_super_admin"),
            $request->get("role_test"),
            $request->get("role_template"),
            $request->get("role_table"),
            $request->get("role_file"),
            $request->get("role_wizard")
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/User/{object_ids}/delete", name="User_delete")
     * @Method(methods={"POST"})
     * @param $object_ids
     * @return Response
     */
    public function deleteAction($object_ids)
    {
        return parent::deleteAction($object_ids);
    }
}
