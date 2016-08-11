<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\ViewTemplate;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\AEntity;

class ViewTemplateService extends AExportableSectionService {

    public function get($object_id, $createNew = false, $secure = true) {
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

    public function save(User $user, $object_id, $name, $description, $accessibility, $protected, $archived, $owner, $groups, $html, $head) {
        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        if ($object === null) {
            $object = new ViewTemplate();
            $new = true;
        }
        $object->setUpdated();
        $object->setUpdatedBy($user);
        if ($head !== null) {
            $object->setHead($head);
        }
        if ($html !== null) {
            $object->setHtml($html);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        if (!$new && $object->isProtected() == $protected && $protected) {
            array_push($errors, "validate.protected.mod");
        }

        if ($this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setProtected($protected);
        $object->setArchived($archived);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        return array("object" => $object, "errors" => $errors);
    }

    public function delete($object_ids, $secure = true) {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;
            if ($object->isProtected() && $secure) {
                array_push($result, array("object" => $object, "errors" => array("validate.protected.mod")));
                continue;
            }
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function entityToArray(AEntity $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (!array_key_exists("ViewTemplate", $map))
            $map["ViewTemplate"] = array();
        if (array_key_exists("id" . $obj["id"], $map["ViewTemplate"])) {
            return(array());
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $new_name = $this->getNextValidName($this->formatImportName($user, $instruction["rename"], $obj), $instruction["action"], $obj["name"]);
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent)
            return $this->importConvert($user, $new_name, $src_ent, $obj, $map, $queue);
        else
            return $this->importNew($user, $new_name, $obj, $map, $queue);
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue) {
        $ent = new ViewTemplate();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setHead($obj["head"]);
        $ent->setHtml($obj["html"]);
        $ent->setOwner($user);
        $ent->setProtected($obj["protected"] == "1");
        $ent->setStarterContent($obj["starterContent"]);
        if (array_key_exists("revision", $obj))
            $ent->setRevision($obj["revision"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);
        $map["ViewTemplate"]["id" . $obj["id"]] = $ent->getId();

        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map) {
        return $this->get($obj["name"]);
    }

    protected function importConvert(User $user, $new_name, $src_ent, $obj, &$map, &$queue) {
        $ent = $this->findConversionSource($obj, $map);
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setHead($obj["head"]);
        $ent->setHtml($obj["html"]);
        $ent->setOwner($user);
        $ent->setProtected($obj["protected"] == "1");
        $ent->setStarterContent($obj["starterContent"]);
        if (array_key_exists("revision", $obj))
            $ent->setRevision($obj["revision"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);
        $map["ViewTemplate"]["id" . $obj["id"]] = $ent->getId();

        $this->onConverted($ent, $src_ent);

        return array("errors" => null, "entity" => $ent);
    }

    protected function onConverted($new_ent, $old_ent) {
        //TODO 
    }

}
