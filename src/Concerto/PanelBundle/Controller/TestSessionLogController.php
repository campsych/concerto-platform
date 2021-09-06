<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestSessionLogService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestSessionLogController extends ASectionController
{

    const ENTITY_NAME = "TestSessionLog";

    public function __construct(EngineInterface $templating, TestSessionLogService $service, TranslatorInterface $translator)
    {
        parent::__construct($templating, $service, $translator);

        $this->entityName = self::ENTITY_NAME;
    }

    /**
     * @Route("/TestSessionLog/collection", name="TestSessionLog_collection")
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestSessionLog/Test/{test_id}/collection", name="TestSessionLog_collection_by_test")
     * @param $test_id
     * @return Response
     */
    public function collectionByTestAction($test_id)
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                'collection' => $this->service->getLatestByTest($test_id)
            )
        );
    }

    /**
     * @Route("/TestSessionLog/{object_ids}/delete", name="TestSessionLog_delete", methods={"POST"})
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/TestSessionLog/Test/{test_id}/clear", name="TestSessionLog_clear", methods={"POST"})
     * @param $test_id
     * @return Response
     */
    public function clearAction($test_id)
    {
        $this->service->clear($test_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}