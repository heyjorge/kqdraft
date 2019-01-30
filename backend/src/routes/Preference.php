<?php

$app->get('/preference', function($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM kqdraft.preference");
    $sth->execute();
    $maps = $sth->fetchAll();

    return $this->response->withJson($maps);
});
