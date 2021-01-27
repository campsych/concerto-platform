<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\ViewTemplate;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\ViewTemplateRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ViewTemplateService extends AExportableSectionService
{

    private $testWizardParamService;

    public function __construct(
        ViewTemplateRepository $repository,
        ValidatorInterface $validator,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TestWizardParamService $testWizardParamService,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $validator, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->testWizardParamService = $testWizardParamService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = null;
        if (is_numeric($object_id)) {
            $object = parent::get($object_id, $createNew, $secure);
        } else {
            $object = $this->repository->findOneByName($object_id);
            if ($secure) {
                $object = $this->authorizeObject($object);
            }
        }
        if ($createNew && $object === null) {
            $object = new ViewTemplate();
        }
        return $object;
    }

    public function save($object_id, $name, $description, $accessibility, $archived, $owner, $groups, $html, $head, $css, $js)
    {
        $errors = array();
        $object = $this->get($object_id);
        $old_name = null;
        if ($object === null) {
            $object = new ViewTemplate();
        }
        if ($head !== null) {
            $object->setHead($head);
        }
        if ($html !== null) {
            $object->setHtml($html);
        }
        if ($css !== null) {
            $object->setCss($css);
        }
        if ($js !== null) {
            $object->setJs($js);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setArchived($archived);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object);
        return array("object" => $object, "errors" => $errors);
    }

    private function update(ViewTemplate $object, $flush = true)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $object->setUpdatedBy($user);
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object, $flush);
            $isRenamed = !$isNew && isset($changeSet["name"]);
            if ($isRenamed) {
                $this->testWizardParamService->onObjectRename($object, $changeSet["name"][0]);
            }
        }
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!isset($renames["ViewTemplate"]))
            $renames["ViewTemplate"] = array();
        if (!isset($map["ViewTemplate"]))
            $map["ViewTemplate"] = array();
        if (isset($map["ViewTemplate"]["id" . $obj["id"]]))
            return array("errors" => null, "entity" => $map["ViewTemplate"]["id" . $obj["id"]]);

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $old_name = $instruction["existing_object_name"];
        $new_name = $this->getNextValidName($this->formatImportName($instruction["rename"], $obj), $instruction["action"], $old_name);
        if ($instruction["action"] != 2 && $old_name != $new_name) {
            $renames["ViewTemplate"][$old_name] = $new_name;
        }

        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($new_name, $src_ent, $obj, $map, $queue);
        } else if ($instruction["action"] == 2 && $src_ent) {
            $map["ViewTemplate"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($new_name, $obj, $map, $queue);
        return $result;
    }

    protected function importNew($new_name, $obj, &$map, &$queue)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $starter_content = $obj["name"] == $new_name ? $obj["starterContent"] : false;

        $ent = new ViewTemplate();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setHead($obj["head"]);
        if (isset($obj["css"]))
            $ent->setCss($obj["css"]);
        if (isset($obj["js"]))
            $ent->setJs($obj["js"]);
        $ent->setHtml($obj["html"]);
        $ent->setOwner($user);
        $ent->setStarterContent($starter_content);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["ViewTemplate"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        return $this->get($obj["name"]);
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, &$queue)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setHead($obj["head"]);
        $ent->setHtml($obj["html"]);
        if (isset($obj["css"]))
            $ent->setCss($obj["css"]);
        if (isset($obj["js"]))
            $ent->setJs($obj["js"]);
        $ent->setOwner($user);
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent);
        $map["ViewTemplate"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    public function getContent($id, $wrapTags = true, $html = true, $css = true, $js = true)
    {
        $template = $this->get($id, false, false);
        if ($template === null) {
            return false;
        }

        $content = "";
        if ($css) {
            if ($wrapTags) $content .= "<style>";
            $content .= $template->getCss();
            if ($wrapTags) $content .= "</style>";
        }
        if ($html) $content .= $template->getHtml();
        if ($js) {
            if ($wrapTags) $content .= "<script>";
            $content .= $template->getJs();
            if ($wrapTags) $content .= "</script>";
        }
        return $content;
    }
}
