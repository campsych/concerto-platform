<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\AEntityRepository;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\AEntity;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Concerto\PanelBundle\Entity\User;

abstract class AExportableSectionService extends ASectionService {

    const FORMAT_COMPRESSED = 'compressed';
    const FORMAT_PLAINTEXT = 'text';

    protected $validator;

    public function __construct(AEntityRepository $repository, RecursiveValidator $validator, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
    }

    public function exportToFile($object_ids, $format = self::FORMAT_COMPRESSED) {
        $result = array();
        $object_ids = explode(",", $object_ids);
        foreach ($object_ids as $object_id) {
            array_push($result, $this->entityToArray($this->get($object_id)));
        }
        if ($format === self::FORMAT_COMPRESSED)
            return gzcompress(json_encode($result, JSON_PRETTY_PRINT), 1);
        else
            return json_encode($result, JSON_PRETTY_PRINT);
    }

    public function getExportFileName($prefix, $object_ids, $format) {
        $ext = ( $format == AExportableSectionService::FORMAT_COMPRESSED ) ? 'concerto' : 'concerto.json';
        $name = $object_ids;
        if (count(explode(",", $object_ids)) == 1) {
            $obj = $this->repository->find($object_ids);
            if ($obj) {
                $name = $obj->getName();
            }
        }
        return $prefix . $name . '.' . $ext;
    }

    protected function formatImportName(User $user, $name, $arr) {
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

    abstract public function entityToArray(AEntity $entity);
}
