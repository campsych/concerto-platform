<?php

namespace Concerto\APIBundle\Service;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use APIBundle\Entity\AEntityRepository;

abstract class AModelService {

    protected $modelName;
    protected $repository;
    protected $validator;
    protected static $excluded_filters = array(
        "access_token",
        "format"
    );

    public function __construct($modelName, AEntityRepository $repository, RecursiveValidator $validator) {
        $this->modelName = $modelName;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function get($id, $format = null) {
        $result = $this->repository->find($id);
        if ($result === null) {
            return null;
        }
        if ($format !== null) {
            return $this->serialize($result, $format);
        }
        return $result;
    }

    public function getCollection($filter, $format = null) {
        foreach (static::$excluded_filters as $key) {
            unset($filter[$key]);
        }
        $operators = array();
        $formatted_filter = $this->formatFilter($filter, $operators);
        $result = $this->repository->filterCollection($formatted_filter, $operators);
        if ($format !== null) {
            return $this->serialize($result, $format);
        }
        return $result;
    }

    public function insert($serializedObject, $format = "json") {
        $object = $this->deserialize($serializedObject, "json");
        $errors = $this->validator->validate($object);
        if (count($errors) > 0) {
            return array("result" => false, "errors" => $errors);
        }
        $this->repository->save($object);
        $result = $this->serialize($object, $format);
        return array("result" => $result, "errors" => "");
    }

    public function update($object, $newSerializedObject, $format = "json") {
        $newObject = $this->deserialize($newSerializedObject, "json");
        $object->set($newObject);
        $errors = $this->validator->validate($object);
        if (count($errors) > 0) {
            return array("result" => false, "errors" => $errors);
        }
        $this->repository->save($object);
        $result = $this->serialize($object, $format);
        return array("result" => $result, "errors" => "");
    }

    public function delete($id) {
        return $this->repository->deleteById($id);
    }

    public function serialize($data, $format = "json") {
        switch ($format) {
            case "xml": $format = "xml";
                break;
            default: $format = "json";
                break;
        }
        $serializer = $this->getSerializerBuilder();
        return $serializer->serialize($data, $format);
    }

    public function deserialize($data, $format = "json") {
        switch ($format) {
            case "xml": $format = "xml";
                break;
            default: $format = "json";
                break;
        }
        $serializer = $this->getSerializerBuilder();
        return $serializer->deserialize($data, $this->modelName, $format);
    }

    protected function getSerializerBuilder() {
        return SerializerBuilder::create()->build();
    }

    protected function formatFilter(&$filter, &$operators) {
        foreach ($filter as $k => $v) {
            unset($filter[$k]);
            $w = explode("_", $k);
            $k = "";
            for ($i = 0; $i < count($w); $i++) {
                if ($i > 0) {
                    $k .= ucfirst($w[$i]);
                } else {
                    $k .= $w[$i];
                }
            }
            $lc = substr($k, strlen($k) - 1, 1);
            $op = "=";
            if ($lc === ">" || $lc === "<" || $lc === "!") {
                $k = substr($k, 0, strlen($k) - 1);
                $op = $lc . $op;
            }
            $operators[$k] = $op;
            $filter[$k] = $v;
        }
        return true;
    }

}
