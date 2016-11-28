<?php

namespace Concerto\PanelBundle\StarterContentUpdateService;

use Concerto\PanelBundle\Entity\TestWizard;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Concerto\PanelBundle\Service\TestWizardService;

class TestWizardUpdateService extends AUpdateService {

    protected $update_history = array(
        array("rev" => 3, "name" => "CAT", "func" => "uh_CAT_3_columnMap"),
        array("rev" => 4, "name" => "linear_test", "func" => "uh_linear_test_4_columnMap"),
        array("rev" => 3, "name" => "polyCAT", "func" => "uh_polyCAT_3_columnMap"),
        array("rev" => 3, "name" => "questionnaire", "func" => "uh_questionnaire_3_columnMap"),
        array("rev" => 2, "name" => "save_data", "func" => "uh_save_data_2_columnMap"),
        array("rev" => 2, "name" => "start_session", "func" => "uh_start_session_2_columnMap"),
        array("rev" => 6, "name" => "start_session", "func" => "uh_start_session_6_viewTemplates")
    );

    private function getChangeJsonValueMap($src_value, $src_map, $dst_value, $dst_map) {
        $decoded_value = json_decode($src_value, true);
        if ($decoded_value === null)
            $decoded_value = $src_value;
        if ($src_map) {
            $src_map_parts = explode("::", $src_map);
            foreach ($src_map_parts as $key) {
                if(!array_key_exists($key, $decoded_value)) {
                    return $dst_value;
                }
                $decoded_value = $decoded_value[$key];
            }
        }

        $decoded_dst_value = json_decode($dst_value, true);
        if ($decoded_dst_value === null)
            $decoded_dst_value = $dst_value;
        $dst_sub_value = &$decoded_dst_value;
        if ($dst_map) {
            $dst_map_parts = explode("::", $dst_map);
            if (!is_array($dst_sub_value))
                $dst_sub_value = array();
            foreach ($dst_map_parts as $key) {
                if (!array_key_exists($key, $dst_sub_value)) {
                    $dst_sub_value[$key] = array();
                }
                $dst_sub_value = &$dst_sub_value[$key];
            }
        }
        $dst_sub_value = $decoded_value;
        $encoded_response = json_encode($decoded_dst_value);
        return $encoded_response !== false ? $encoded_response : $decoded_dst_value;
    }

    private function updateTestWizardParam(TestWizard $wizard, TestWizardService $service, $src_name, $src_map, $dst_name, $dst_map) {
        foreach ($wizard->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                $service->repository->refresh($node);
                $src_port = null;
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == $src_name) {
                        $src_port = $port;
                        break;
                    }
                }

                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == $dst_name) {
                        $val = $this->getChangeJsonValueMap($src_port->getValue(), $src_map, $port->getValue(), $dst_map);
                        $port->setValue($val);
                        $port->setDefaultValue(false);
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;

            $src_var = null;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == $src_name) {
                    $src_var = $var;
                    break;
                }
            }

            foreach ($test->getVariables() as $var) {
                if ($var->getName() == $dst_name) {
                    $val = $this->getChangeJsonValueMap($src_var->getValue(), $src_map, $var->getValue(), $dst_map);
                    $var->setValue($val);
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function CAT_3_columnMap_convertItemBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_question_column", $val) && array_key_exists("column", $val["custom_question_column"]);
        $convertable &= array_key_exists("custom_response_options_column", $val) && array_key_exists("column", $val["custom_response_options_column"]);
        $convertable &= array_key_exists("custom_a_column", $val) && array_key_exists("column", $val["custom_a_column"]);
        $convertable &= array_key_exists("custom_b_column", $val) && array_key_exists("column", $val["custom_b_column"]);
        $convertable &= array_key_exists("custom_c_column", $val) && array_key_exists("column", $val["custom_c_column"]);
        $convertable &= array_key_exists("custom_d_column", $val) && array_key_exists("column", $val["custom_d_column"]);
        $convertable &= array_key_exists("custom_correct_column", $val) && array_key_exists("column", $val["custom_correct_column"]);
        $convertable &= array_key_exists("custom_test_id_column", $val) && array_key_exists("column", $val["custom_test_id_column"]);
        $convertable &= array_key_exists("custom_cb_group_column", $val) && array_key_exists("column", $val["custom_cb_group_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $question = $val["custom_question_column"]["column"];
        $response_options = $val["custom_response_options_column"]["column"];
        $a = $val["custom_a_column"]["column"];
        $b = $val["custom_b_column"]["column"];
        $c = $val["custom_c_column"]["column"];
        $d = $val["custom_d_column"]["column"];
        $correct = $val["custom_correct_column"]["column"];
        $test_id = $val["custom_test_id_column"]["column"];
        $cb_group = $val["custom_cb_group_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "question" => $question,
                "response_options" => $response_options,
                "a" => $a,
                "b" => $b,
                "c" => $c,
                "d" => $d,
                "correct" => $correct,
                "test_id" => $test_id,
                "cb_group" => $cb_group
            )
        );
        return $val;
    }

    private function CAT_3_columnMap_convertResponseBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_item_id_column", $val) && array_key_exists("column", $val["custom_item_id_column"]);
        $convertable &= array_key_exists("custom_response_column", $val) && array_key_exists("column", $val["custom_response_column"]);
        $convertable &= array_key_exists("custom_time_taken_column", $val) && array_key_exists("column", $val["custom_time_taken_column"]);
        $convertable &= array_key_exists("custom_session_internal_id_column", $val) && array_key_exists("column", $val["custom_session_internal_id_column"]);
        $convertable &= array_key_exists("custom_correct_column", $val) && array_key_exists("column", $val["custom_correct_column"]);
        $convertable &= array_key_exists("custom_theta_column", $val) && array_key_exists("column", $val["custom_theta_column"]);
        $convertable &= array_key_exists("custom_sem_column", $val) && array_key_exists("column", $val["custom_sem_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $item_id = $val["custom_item_id_column"]["column"];
        $response = $val["custom_response_column"]["column"];
        $time_taken = $val["custom_time_taken_column"]["column"];
        $session_internal_id = $val["custom_session_internal_id_column"]["column"];
        $correct = $val["custom_correct_column"]["column"];
        $theta = $val["custom_theta_column"]["column"];
        $sem = $val["custom_sem_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "item_id" => $item_id,
                "response" => $response,
                "time_taken" => $time_taken,
                "session_internal_id" => $session_internal_id,
                "correct" => $correct,
                "theta" => $theta,
                "sem" => $sem
            )
        );
        return $val;
    }

    protected function uh_CAT_3_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "item_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->CAT_3_columnMap_convertItemBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                    if ($var->getName() == "response_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->CAT_3_columnMap_convertResponseBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "item_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->CAT_3_columnMap_convertItemBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
                if ($var->getName() == "response_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->CAT_3_columnMap_convertResponseBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function linear_test_4_columnMap_convertItemBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_question_column", $val) && array_key_exists("column", $val["custom_question_column"]);
        $convertable &= array_key_exists("custom_order_column", $val) && array_key_exists("column", $val["custom_order_column"]);
        $convertable &= array_key_exists("custom_response_options", $val) && array_key_exists("column", $val["custom_response_options"]);
        $convertable &= array_key_exists("custom_correct_column", $val) && array_key_exists("column", $val["custom_correct_column"]);
        $convertable &= array_key_exists("custom_trait_column", $val) && array_key_exists("column", $val["custom_trait_column"]);
        $convertable &= array_key_exists("custom_test_id_column", $val) && array_key_exists("column", $val["custom_test_id_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $question = $val["custom_question_column"]["column"];
        $order = $val["custom_order_column"]["column"];
        $response_options = $val["custom_response_options"]["column"];
        $correct = $val["custom_correct_column"]["column"];
        $trait = $val["custom_trait_column"]["column"];
        $test_id = $val["custom_test_id_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "question" => $question,
                "order" => $order,
                "response_options" => $response_options,
                "correct" => $correct,
                "trait" => $trait,
                "test_id" => $test_id
            )
        );
        return $val;
    }

    private function linear_test_4_columnMap_convertResponseBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_item_id_column", $val) && array_key_exists("column", $val["custom_item_id_column"]);
        $convertable &= array_key_exists("custom_response_column", $val) && array_key_exists("column", $val["custom_response_column"]);
        $convertable &= array_key_exists("custom_trait_column", $val) && array_key_exists("column", $val["custom_trait_column"]);
        $convertable &= array_key_exists("custom_correct_column", $val) && array_key_exists("column", $val["custom_correct_column"]);
        $convertable &= array_key_exists("custom_session_internal_id_column", $val) && array_key_exists("column", $val["custom_session_internal_id_column"]);
        $convertable &= array_key_exists("custom_time_taken_column", $val) && array_key_exists("column", $val["custom_time_taken_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $item_id = $val["custom_item_id_column"]["column"];
        $response = $val["custom_response_column"]["column"];
        $trait = $val["custom_trait_column"]["column"];
        $correct = $val["custom_correct_column"]["column"];
        $session_internal_id = $val["custom_session_internal_id_column"]["column"];
        $time_taken = $val["custom_time_taken_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "item_id" => $item_id,
                "response" => $response,
                "time_taken" => $time_taken,
                "session_internal_id" => $session_internal_id,
                "correct" => $correct,
                "trait" => $trait
            )
        );
        return $val;
    }

    protected function uh_linear_test_4_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "item_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->linear_test_4_columnMap_convertItemBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                    if ($var->getName() == "response_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->linear_test_4_columnMap_convertResponseBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "item_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->linear_test_4_columnMap_convertItemBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
                if ($var->getName() == "response_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->linear_test_4_columnMap_convertResponseBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function polyCAT_3_columnMap_convertItemBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_question_column", $val) && array_key_exists("column", $val["custom_question_column"]);
        $convertable &= array_key_exists("custom_response_options_column", $val) && array_key_exists("column", $val["custom_response_options_column"]);
        $convertable &= array_key_exists("custom_irt_discrimination_column", $val) && array_key_exists("column", $val["custom_irt_discrimination_column"]);
        $convertable &= array_key_exists("custom_cb_group_column", $val) && array_key_exists("column", $val["custom_cb_group_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $question = $val["custom_question_column"]["column"];
        $response_options = $val["custom_response_options_column"]["column"];
        $irt_discrimination = $val["custom_irt_discrimination_column"]["column"];
        $cb_group = $val["custom_cb_group_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "question" => $question,
                "response_options" => $response_options,
                "irt_discrimination" => $irt_discrimination,
                "cb_group" => $cb_group
            )
        );
        return $val;
    }

    private function polyCAT_3_columnMap_convertResponseBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_item_id_column", $val) && array_key_exists("column", $val["custom_item_id_column"]);
        $convertable &= array_key_exists("custom_response_column", $val) && array_key_exists("column", $val["custom_response_column"]);
        $convertable &= array_key_exists("custom_time_taken_column", $val) && array_key_exists("column", $val["custom_time_taken_column"]);
        $convertable &= array_key_exists("custom_session_internal_id_column", $val) && array_key_exists("column", $val["custom_session_internal_id_column"]);
        $convertable &= array_key_exists("custom_theta_column", $val) && array_key_exists("column", $val["custom_theta_column"]);
        $convertable &= array_key_exists("custom_sem_column", $val) && array_key_exists("column", $val["custom_sem_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $item_id = $val["custom_item_id_column"]["column"];
        $response = $val["custom_response_column"]["column"];
        $time_taken = $val["custom_time_taken_column"]["column"];
        $session_internal_id = $val["custom_session_internal_id_column"]["column"];
        $theta = $val["custom_theta_column"]["column"];
        $sem = $val["custom_sem_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "item_id" => $item_id,
                "response" => $response,
                "time_taken" => $time_taken,
                "session_internal_id" => $session_internal_id,
                "theta" => $theta,
                "sem" => $sem
            )
        );
        return $val;
    }

    protected function uh_polyCAT_3_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "item_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->polyCAT_3_columnMap_convertItemBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                    if ($var->getName() == "response_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->polyCAT_3_columnMap_convertResponseBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "item_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->polyCAT_3_columnMap_convertItemBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
                if ($var->getName() == "response_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->polyCAT_3_columnMap_convertResponseBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function questionnaire_3_columnMap_convertItemBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_question_column", $val) && array_key_exists("column", $val["custom_question_column"]);
        $convertable &= array_key_exists("custom_order_column", $val) && array_key_exists("column", $val["custom_order_column"]);
        $convertable &= array_key_exists("custom_trait_column", $val) && array_key_exists("column", $val["custom_trait_column"]);
        $convertable &= array_key_exists("custom_reversed_score_column", $val) && array_key_exists("column", $val["custom_reversed_score_column"]);
        $convertable &= array_key_exists("custom_test_id_column", $val) && array_key_exists("column", $val["custom_test_id_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $question = $val["custom_question_column"]["column"];
        $order = $val["custom_order_column"]["column"];
        $trait = $val["custom_trait_column"]["column"];
        $reversed_score = $val["custom_reversed_score_column"]["column"];
        $test_id = $val["custom_test_id_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "question" => $question,
                "order" => $order,
                "trait" => $trait,
                "reversed_score" => $reversed_score,
                "test_id" => $test_id
            )
        );
        return $val;
    }

    private function questionnaire_3_columnMap_convertResponseBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_session_internal_id_column", $val) && array_key_exists("column", $val["custom_session_internal_id_column"]);
        $convertable &= array_key_exists("custom_question_id_column", $val) && array_key_exists("column", $val["custom_question_id_column"]);
        $convertable &= array_key_exists("custom_value_column", $val) && array_key_exists("column", $val["custom_value_column"]);
        $convertable &= array_key_exists("custom_score_column", $val) && array_key_exists("column", $val["custom_score_column"]);
        $convertable &= array_key_exists("custom_trait_column", $val) && array_key_exists("column", $val["custom_trait_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $session_internal_id = $val["custom_session_internal_id_column"]["column"];
        $question_id = $val["custom_question_id_column"]["column"];
        $value = $val["custom_value_column"]["column"];
        $score = $val["custom_score_column"]["column"];
        $trait = $val["custom_trait_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "session_internal_id" => $session_internal_id,
                "question_id" => $question_id,
                "value" => $value,
                "score" => $score,
                "trait" => $trait
            )
        );
        return $val;
    }

    protected function uh_questionnaire_3_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "item_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->questionnaire_3_columnMap_convertItemBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                    if ($var->getName() == "response_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->questionnaire_3_columnMap_convertResponseBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "item_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->questionnaire_3_columnMap_convertItemBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
                if ($var->getName() == "response_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->questionnaire_3_columnMap_convertResponseBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function save_data_2_columnMap_convertDataBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_session_internal_id_column", $val) && array_key_exists("column", $val["custom_session_internal_id_column"]);
        $convertable &= array_key_exists("custom_value_column", $val) && array_key_exists("column", $val["custom_value_column"]);
        $convertable &= array_key_exists("custom_name_column", $val) && array_key_exists("column", $val["custom_name_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $session_internal_id = $val["custom_session_internal_id_column"]["column"];
        $name = $val["custom_name_column"]["column"];
        $value = $val["custom_value_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "session_internal_id" => $session_internal_id,
                "name" => $name,
                "value" => $value
            )
        );
        return $val;
    }

    protected function uh_save_data_2_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "data_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->save_data_2_columnMap_convertDataBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "data_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->save_data_2_columnMap_convertDataBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    private function start_session_2_columnMap_convertSessionBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_user_id_login", $val) && array_key_exists("column", $val["custom_user_id_login"]);
        $convertable &= array_key_exists("custom_test_id_column", $val) && array_key_exists("column", $val["custom_test_id_column"]);
        $convertable &= array_key_exists("custom_internal_id_column", $val) && array_key_exists("column", $val["custom_internal_id_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $user_id = $val["custom_user_id_login"]["column"];
        $test_id = $val["custom_test_id_column"]["column"];
        $session_internal_id = $val["custom_internal_id_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "user_id" => $user_id,
                "test_id" => $test_id,
                "session_internal_id" => $session_internal_id
            )
        );
        return $val;
    }

    private function start_session_2_columnMap_convertUserBank($val) {
        $convertable = array_key_exists("custom_table", $val);
        $convertable &= array_key_exists("custom_login_column", $val) && array_key_exists("column", $val["custom_login_column"]);
        $convertable &= array_key_exists("custom_password_column", $val) && array_key_exists("column", $val["custom_password_column"]);
        $convertable &= array_key_exists("custom_encryption_column", $val) && array_key_exists("column", $val["custom_encryption_column"]);
        $convertable &= array_key_exists("custom_test_id_column", $val) && array_key_exists("column", $val["custom_test_id_column"]);
        $convertable &= array_key_exists("custom_email_column", $val) && array_key_exists("column", $val["custom_email_column"]);
        $convertable &= array_key_exists("custom_enabled_column", $val) && array_key_exists("column", $val["custom_enabled_column"]);
        if (!$convertable)
            return false;

        $table = $val["custom_table"];
        $login = $val["custom_login_column"]["column"];
        $password = $val["custom_password_column"]["column"];
        $encryption = $val["custom_encryption_column"]["column"];
        $test_id = $val["custom_test_id_column"]["column"];
        $email = $val["custom_email_column"]["column"];
        $enabled = $val["custom_enabled_column"]["column"];

        $val["custom_table"] = array(
            "table" => $table,
            "columns" => array(
                "login" => $login,
                "password" => $password,
                "encryption" => $encryption,
                "test_id" => $test_id,
                "email" => $email,
                "enabled" => $enabled
            )
        );
        return $val;
    }

    protected function uh_start_session_2_columnMap($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        foreach ($new_ent->getResultingTests() as $test) {
            foreach ($test->getSourceForNodes() as $node) {
                foreach ($node->getPorts() as $port) {
                    $var = $port->getVariable();
                    if ($var == null || $var->getType() != 0)
                        continue;

                    if ($var->getName() == "session_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->start_session_2_columnMap_convertSessionBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                    if ($var->getName() == "user_bank") {
                        $encoded_val = $port->getValue();
                        $val = json_decode($encoded_val, true);
                        $val = $this->start_session_2_columnMap_convertUserBank($val);
                        if ($val === false)
                            continue;
                        $port->setValue(json_encode($val));
                        $service->testNodePortService->update($port);
                    }
                }
            }

            if ($test->isStarterContent())
                continue;
            foreach ($test->getVariables() as $var) {
                if ($var->getName() == "session_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->start_session_2_columnMap_convertSessionBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
                if ($var->getName() == "user_bank") {
                    $encoded_val = $var->getValue();
                    $val = json_decode($encoded_val, true);
                    $val = $this->start_session_2_columnMap_convertUserBank($val);
                    if ($val === false)
                        continue;
                    $var->setValue(json_encode($val));
                    $service->testVariableService->update($user, $var);
                }
            }
        }
    }

    protected function uh_start_session_6_viewTemplates($user, TestWizardService $service, TestWizard $new_ent, TestWizard $old_ent) {
        $this->updateTestWizardParam($new_ent, $service, "title", null, "login_form", "title");
        $this->updateTestWizardParam($new_ent, $service, "paragraph", null, "login_form", "paragraph");
        $this->updateTestWizardParam($new_ent, $service, "button_label", null, "login_form", "login_button_label");
        $this->updateTestWizardParam($new_ent, $service, "failed_alert", null, "login_form", "failed_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "enabled", "registration_enabled", null);
        $this->updateTestWizardParam($new_ent, $service, "registration", "registration_button_label", "login_form", "register_button_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "paragraph", "registration_form", "paragraph");
        $this->updateTestWizardParam($new_ent, $service, "registration", "login_label", "registration_form", "login_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "login_alert", "registration_form", "login_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "password_label", "registration_form", "password_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "password_alert", "registration_form", "password_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "password_confirmation_label", "registration_form", "password_confirmation_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "password_confirmation_alert", "registration_form", "password_confirmation_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "email_label", "registration_form", "email_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "email_alert", "registration_form", "email_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "form_button_label", "registration_form", "register_button_label");
        $this->updateTestWizardParam($new_ent, $service, "registration", "login_exists_alert", "registration_form", "login_exists_alert");
        $this->updateTestWizardParam($new_ent, $service, "registration", "title", "registration_form", "title");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_request_page::title", "email_conf_request_page", "title");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_request_page::content", "email_conf_request_page", "content");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_success_page::title", "email_success_request_page", "title");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_success_page::content", "email_success_request_page", "content");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_email::sender", "email_confirmation", "sender");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_email::subject", "email_confirmation", "subject");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_email::paragraph", "email_confirmation", "paragraph");
        $this->updateTestWizardParam($new_ent, $service, "registration", "ec_email::url", "registration", "link");
    }

}
