<?php
class TestPDO extends Controllers {
    public function index() {
        $db = (new Lgs_enviosModel())->getConexion();
        var_dump($db->query("SELECT DATABASE()")->fetch());
        var_dump($db->query("SELECT COUNT(*) FROM lgs_envios")->fetch());
    }
}
