<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Teams_graph
{
    private $ci;
    private $config;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->config->load('teams', true);
        $this->config = $this->ci->config->item('teams');
    }

    public function get_user_presence($aad_user_id)
    {
        $presences = $this->get_users_presence([$aad_user_id]);
        if (empty($presences[$aad_user_id])) {
            return null;
        }

        return $presences[$aad_user_id];
    }

    public function get_users_presence(array $aad_user_ids)
    {
        $aad_user_ids = array_values(array_unique(array_filter($aad_user_ids)));
        if (empty($aad_user_ids)) {
            return [];
        }

        $token = $this->get_access_token();
        if (!$token) {
            return [];
        }

        $url = rtrim($this->config['graph_base_url'], '/') . '/communications/getPresencesByUserId';
        $payload = json_encode(['ids' => $aad_user_ids]);
        $response = $this->request('POST', $url, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ], $payload);

        if (!$response || empty($response['value']) || !is_array($response['value'])) {
            $this->ci->log_message('error', 'Teams_graph: invalid bulk presence response.');
            return [];
        }

        $mapped = [];
        foreach ($response['value'] as $presence) {
            if (empty($presence['id']) || empty($presence['availability']) || empty($presence['activity'])) {
                continue;
            }
            $mapped[$presence['id']] = [
                'availability' => $presence['availability'],
                'activity' => $presence['activity'],
            ];
        }

        return $mapped;
    }

    private function get_access_token()
    {
        if (empty($this->config['tenant_id']) || empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            $this->ci->log_message('error', 'Teams_graph: missing Azure AD credentials in config/teams.php');
            return null;
        }

        $token_url = sprintf($this->config['token_url'], $this->config['tenant_id']);
        $payload = http_build_query([
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        $response = $this->request('POST', $token_url, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], $payload);

        if (!$response || empty($response['access_token'])) {
            $this->ci->log_message('error', 'Teams_graph: failed to obtain access token');
            return null;
        }

        return $response['access_token'];
    }

    private function request($method, $url, array $headers = [], $payload = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->ci->log_message('error', 'Teams_graph: cURL error - ' . $err);
            return null;
        }

        $data = json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $this->ci->log_message('error', 'Teams_graph: HTTP ' . $status . ' response - ' . $raw);
            return null;
        }

        return $data;
    }
}
