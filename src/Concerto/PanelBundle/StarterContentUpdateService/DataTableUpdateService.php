<?php

namespace Concerto\PanelBundle\StarterContentUpdateService;

use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Service\DataTableService;
use Concerto\PanelBundle\Repository\DataTableRepository;

class DataTableUpdateService extends AUpdateService {

    protected $update_history = array(
        array("rev" => 2, "name" => "default_data_table", "func" => "uh_default_data_table_2_session"),
        array("rev" => 2, "name" => "default_linear_response_table", "func" => "uh_default_linear_response_table_2_session"),
        array("rev" => 2, "name" => "default_cat_response_table", "func" => "uh_default_cat_response_table_2_session"),
        array("rev" => 2, "name" => "default_polycat_response_table", "func" => "uh_default_polycat_response_table_2_session"),
        array("rev" => 2, "name" => "default_questionnaire_response_table", "func" => "uh_default_questionnaire_response_table_2_session")
    );

    private function convert_internal_session_id_to_session_id(DataTableService $service, $table_name) {
        $service->dbDataDao->connection->createQueryBuilder()->update($table_name)->set("session_id", "CONCAT('i',session_internal_id)")->execute();
    }

    protected function uh_default_data_table_2_session($user, DataTableService $service, DataTable $new_ent, DataTable $old_ent) {
        $this->convert_internal_session_id_to_session_id($service, "default_data_table");
        $service->dbStructureService->removeColumn("default_data_table", "session_internal_id");
    }

    protected function uh_default_linear_response_table_2_session($user, DataTableService $service, DataTable $new_ent, DataTable $old_ent) {
        $this->convert_internal_session_id_to_session_id($service, "default_linear_response_table");
        $service->dbStructureService->removeColumn("default_linear_response_table", "session_internal_id");
    }

    protected function uh_default_cat_response_table_2_session($user, DataTableService $service, DataTable $new_ent, DataTable $old_ent) {
        $this->convert_internal_session_id_to_session_id($service, "default_cat_response_table");
        $service->dbStructureService->removeColumn("default_cat_response_table", "session_internal_id");
    }

    protected function uh_default_polycat_response_table_2_session($user, DataTableService $service, DataTable $new_ent, DataTable $old_ent) {
        $this->convert_internal_session_id_to_session_id($service, "default_polycat_response_table");
        $service->dbStructureService->removeColumn("default_polycat_response_table", "session_internal_id");
    }

    protected function uh_default_questionnaire_response_table_2_session($user, DataTableService $service, DataTable $new_ent, DataTable $old_ent) {
        $this->convert_internal_session_id_to_session_id($service, "default_questionnaire_response_table");
        $service->dbStructureService->removeColumn("default_questionnaire_response_table", "session_internal_id");
    }

}
