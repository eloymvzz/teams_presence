<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Teams_presence_log_model extends CI_Model
{
    public function insert_presence($user_id, $availability, $activity, $recorded_at)
    {
        $this->db->insert('teams_presence_log', [
            'user_id' => $user_id,
            'availability' => $availability,
            'activity' => $activity,
            'recorded_at' => $recorded_at,
        ]);

        return $this->db->affected_rows() > 0;
    }
}
