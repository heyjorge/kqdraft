<?php

$app->get('/map', function($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM kqdraft.map");
    $sth->execute();
    $maps = $sth->fetchAll();

    return $this->response->withJson($maps);
});
