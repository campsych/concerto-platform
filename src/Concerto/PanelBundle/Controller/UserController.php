<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\UserService;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class UserController extends ASectionController
{

    const ENTITY_NAME = "User";

    public function __construct(EngineInterface $templating, UserService $service, TranslatorInterface $translator, TokenStorage $securityTokenStorage)
    {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
    }

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

}
