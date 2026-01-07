<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->input->is_cli_request()) {
            show_404();
        }

        $this->load->model('Teams_user_model', 'teams_user');
        $this->load->model('Teams_presence_log_model', 'presence_log');
        $this->load->library('Teams_graph');
    }

    public function presence()
    {
        $users = $this->teams_user->get_active_users();
        if (empty($users)) {
            log_message('info', 'Cron/presence: no active Teams users found.');
            echo "No active users found." . PHP_EOL;
            return;
        }

        $aad_user_ids = array_column($users, 'aad_user_id');
        $presence_map = $this->teams_graph->get_users_presence($aad_user_ids);
        if (empty($presence_map)) {
            log_message('error', 'Cron/presence: failed to fetch bulk presence data.');
            echo "Presence sync failed." . PHP_EOL;
            return;
        }

        $recorded_at = date('Y-m-d H:i:s');
        foreach ($users as $user) {
            $presence = $presence_map[$user['aad_user_id']] ?? null;
            if (!$presence) {
                log_message('error', 'Cron/presence: missing presence for user ID ' . $user['id']);
                continue;
            }

            $this->presence_log->insert_presence(
                $user['id'],
                $presence['availability'],
                $presence['activity'],
                $recorded_at
            );
        }

        echo "Presence sync completed." . PHP_EOL;
    }
}
