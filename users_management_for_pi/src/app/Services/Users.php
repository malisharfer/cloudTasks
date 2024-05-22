<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;

class Users
{
    public function __construct(protected PendingRequest $httpClient)
    {
    }

    public function getUsersGroupName($group_id)
    {
        $response = $this->httpClient->get("/groups/$group_id")->throw();

        return $response['displayName'];
    }

    public function getUsersGroupMembers($group_id)
    {
        $response = $this->httpClient->get("/groups/$group_id/members")->throw();

        return $response['value'];
    }

    public function checkMemberGroups($user_id, $group_ids)
    {
        $response = $this->httpClient->post("/users/$user_id/checkMemberGroups", ['groupIds' => $group_ids])->throw();

        return $response;
    }

    public function addUser($request)
    {
        $response = $this->httpClient->post('/users', $request)->throw()->json();
        return $response;
    }
}