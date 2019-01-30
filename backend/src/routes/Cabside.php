<?php

$app->get('/cabside', function($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM kqdraft.cabinet_side");
    $sth->execute();
    $cabsides = $sth->fetchAll();

    return $this->response->withJson($cabsides);
});
