<?php

namespace App\Services;

use App\Enums\Users\Role;

class GetUsers
{
    protected $azureClient;

    public function __construct(AzureClient $azureClient)
    {
        $this->azureClient = $azureClient;
    }

    public function getAllGroupMembers($group_ids)
    {
        $all_members = [];

        foreach ($group_ids as $group_id) {
            $group_name = $this->azureClient->users()->getUsersGroupName($group_id);
            $group_members = $this->azureClient->users()->getUsersGroupMembers($group_id);

            foreach ($group_members as $group_member) {
                if ($group_member['@odata.type'] == '#microsoft.graph.group') {
                    $inner_members = $this->getInnerGroupMembers($group_member['id'], $group_name);
                    $all_members = array_merge($all_members, $inner_members);
                } else {
                    $all_members[] = $this->fillArray($group_member['displayName'], $group_member['mail'], $group_name);
                }
            }
        }

        return $all_members;
    }

    public function getInnerGroupMembers($group_id, $groupName)
    {
        $members = [];
        $inner_group_members = $this->azureClient->users()->getUsersGroupMembers($group_id);
        foreach ($inner_group_members as $inner_member) {
            $members[] = $this->fillArray($inner_member['displayName'], $inner_member['mail'], $groupName);
        }

        return $members;
    }

    public function fillArray($display_name, $mail, $group_name)
    {
        return [
            'name' => $display_name,
            'email' => $mail,
            'role' => $group_name == 'user-cre-app-clients' ? Role::User : Role::Admin,
        ];
    }

    public function checkGroupMemberships($user_id, $group_ids)
    {
        $response = $this->azureClient->users()->checkMemberGroups($user_id, $group_ids);
        if ($response->successful()) {
            $groupMemberships = $response->json()['value'];
            if (in_array($group_ids[1], $groupMemberships)) {
                return Role::Admin;
            } elseif (in_array($group_ids[0], $groupMemberships)) {
                return Role::User;
            }
        }

        return false;
    }
}
