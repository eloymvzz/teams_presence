<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Teams_user_model extends CI_Model
{
    public function get_active_users()
    {
        return $this->db
            ->select('id, aad_user_id')
            ->from('teams_users')
            ->where('is_active', 1)
            ->get()
            ->result_array();
    }
}
