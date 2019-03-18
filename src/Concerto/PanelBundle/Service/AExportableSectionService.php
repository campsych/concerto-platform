<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\AEntityRepository;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AExportableSectionService extends ASectionService
{
    protected $validator;

    public function __construct(AEntityRepository $repository, ValidatorInterface $validator, AuthorizationCheckerInterface $securityAuthorizationChecker)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
    }

    public function getExportFileName($prefix, $instructions, $format)
    {
        $ext = null;
        switch ($format) {
            case "yml":
            case "json":
                $ext = "concerto." . $format;
                break;
            case "compressed":
                $ext = "concerto";
                break;
        }
        $name = "";
        if (count($instructions) == 1) {
            $name = $instructions[0]["name"];
        }
        return $prefix . $name . '.' . $ext;
    }

    protected function formatImportName(User $user, $name, $arr)
    {
        if ($name != "") {
            $name = str_replace("{{id}}", $arr['id'], $name);
            $name = str_replace("{{name}}", $arr['name'], $name);
            $name = str_replace("{{user_id}}", $user->getId(), $name);
            $name = str_replace("{{user_username}}", $user->getUsername(), $name);
        } else {
            $name = $arr['name'];
        }
        return $name;
    }

    protected function getNextValidName($name, $action, $old_name)
    {
        while ($this->doesNameExist($name) && ($action != 1 || $name != $old_name)) {
            $index = strripos($name, "_");
            if ($index !== -1) {
                $prefix = substr($name, 0, $index);
                $suffix = substr($name, $index + 1);
                if (is_numeric($suffix)) {
                    $suffix += 1;
                    $name = $prefix . "_" . $suffix;
                    continue;
                }
            }
            $name = $name . "_1";
        }
        return $name;
    }

    protected function doesNameExist($name)
    {
        return $this->repository->findOneBy(array("name" => $name)) != null;
    }

    public function convertToExportable($arr, $instruction = null)
    {
        unset($arr["updatedOn"]);
        unset($arr["updatedBy"]);
        unset($arr["owner"]);
        return $arr;
    }

}
