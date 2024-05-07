<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\User;

class GetUsers {
    public function getAccessToken($client_id, $client_secret, $tenant_id, $scope, $authority) {
        $token = Http::asForm()->post($authority, [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'resource' => 'https://graph.microsoft.com/',
            'grant_type' => 'client_credentials',
        ])->json();
        
        $accessToken = $token['access_token'];
        return $accessToken;
    }
  
    public function getGroupName($graph_url,$group_id, $accessToken) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get("$graph_url/groups/$group_id");
        
        $groupName = $response['displayName'];
        return $groupName;
    }

    public function getGroupMembers($graph_url,$group_id, $accessToken) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get("$graph_url/groups/$group_id/members");
        
        $groupMembers = $response['value'];
        return $groupMembers;
    }

    public function getAllGroupMembers($graph_url,$group_ids, $accessToken) {
        $allMembers = [];
    
        foreach ($group_ids as $group_id) {
            $groupName = $this->getgroupName($graph_url,$group_id, $accessToken);
            $groupMembers = $this->getGroupMembers($graph_url,$group_id, $accessToken);
    
            foreach ($groupMembers as $groupMember) {
                if ($groupMember["@odata.type"] == "#microsoft.graph.group") {
                    $innerGroupMembers = $this->getGroupMembers($graph_url,$groupMember["id"], $accessToken);
                    foreach ($innerGroupMembers as $innerMember) {
                        $allMembers[] = $this->fillArray($innerMember['displayName'],$innerMember['mail'],$groupName);
                    }
                } else {
                    $allMembers[] = $this->fillArray($groupMember['displayName'],$groupMember['mail'],$groupName);
                }
            }
        }
        return $allMembers;
    }

    public function fillArray($displayName,$mail,$groupName){
        return  [
            "name" => $displayName,
            "email" => $mail,
            "role" => $groupName == "user-cre-app-clients"? "User":"Admin",
        ];
    }

    public function getUsersFromGroups($getUsers = null){
        if ($getUsers === null) {
            $getUsers = new GetUsers();
        }
        $client_id = config('services.azure.client_id');
        $client_secret = config('services.azure.client_secret');
        $tenant_id = config('services.azure.tenant');
        $authority = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/token?api-version=1.0';
        $graph_url = config('services.azure.graph_url');
        $group_id_clients = config('services.azure.group_id_clients');
        $group_id_admins = config('services.azure.group_id_admins');
        $scope = ["https://graph.microsoft.com/.default"];
        $group_ids = [$group_id_admins, $group_id_clients];

        $accessToken = $getUsers->getAccessToken($client_id, $client_secret, $tenant_id, $scope, $authority);
        if ($accessToken) {
            $allMembers = $getUsers->getAllGroupMembers($graph_url,$group_ids, $accessToken);
            return $allMembers;
        }
    }
}
