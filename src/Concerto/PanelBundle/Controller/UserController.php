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
class UserController extends ASectionController {

    const ENTITY_NAME = "User";

    private $request;

    public function __construct(EngineInterface $templating, UserService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
        $this->request = $request;
    }

    public function saveAction($object_id) {
        $result = $this->service->save(
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->request->get("accessibility"), //
                $this->request->get("archived") === "1", //
                $this->service->get($this->request->get("owner")), //
                $this->request->get("groups"), //
                $this->request->get("email"), //
                $this->request->get("username"), //
                $this->request->get("password"), //
                $this->request->get("passwordConfirmation"), //
                $this->request->get("role_super_admin"), //
                $this->request->get("role_test"), //
                $this->request->get("role_template"), //
                $this->request->get("role_table"), //
                $this->request->get("role_file"), //
                $this->request->get("role_wizard")
        );
        return $this->getSaveResponse($result);
    }

}
