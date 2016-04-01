<?php

namespace Concerto\MinimalRemoteTestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    public function indexAction($node_id, $test_slug, $params) {
        return $this->render('ConcertoMinimalRemoteTestBundle:Default:index.html.twig', array(
                    'node_id' => $node_id,
                    'test_slug' => $test_slug,
                    'params' => $params
        ));
    }

}
