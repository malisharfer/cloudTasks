<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use App\Services\GetUsers;
use App\Models\User;
use Tests\TestCase;
use Mockery;


class GetUsersTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testGetAccessToken()
    {
        Http::fake([
            'https://test-auth.com' => Http::response(['access_token' => 'test_access_token'], 200),
        ]);

        $getUsers = new GetUsers();
        $accessToken = $getUsers->getAccessToken('client_id', 'client_secret', 'tenant_id', 'scope', 'https://test-auth.com');
        
        $this->assertEquals('test_access_token', $accessToken);
    }

    public function testGetGroupName()
    {
        Http::fake([
            'https://test-graph.com/groups/test_group_id' => Http::response(['displayName' => 'test_group_name'], 200),
        ]);

        $getUsers = new GetUsers();
        $groupName = $getUsers->getGroupName('https://test-graph.com', 'test_group_id', 'test_access_token');

        $this->assertEquals('test_group_name', $groupName);
    }

    public function testGetGroupMembers()
    {
        Http::fake([
            'https://test-graph.com/groups/test_group_id/members' => Http::response(['value' => ['member1', 'member2']], 200),
        ]);

        $getUsers = new GetUsers();
        $groupMembers = $getUsers->getGroupMembers('https://test-graph.com', 'test_group_id', 'test_access_token');

        $this->assertEquals(['member1', 'member2'], $groupMembers);
    }

    public function testGetAllGroupMembers()
    {
        $getUsersMock = Mockery::mock(GetUsers::class)->makePartial();
        $getUsersMock->shouldReceive('getGroupName')->andReturn('Test Group Name');
        $getUsersMock->shouldReceive('getGroupMembers')->andReturnUsing(function ($graph_url, $group_id, $accessToken) {
            return [
                ['@odata.type' => "#microsoft.graph.group",'displayName' => 'User1','mail' => 'user1@example.com', 'id' => "innerGroupId"],
                ['@odata.type' => '#microsoft.graph.user','displayName' => 'User2','mail' => 'user2@example.com']
            ];
        });
        
        $expectedResult = [
            ['name' => 'User1','email' => 'user1@example.com','role' => 'Admin'],
            ['name' => 'User2','email' => 'user2@example.com','role' => 'Admin'],
            ['name' => 'User2','email' => 'user2@example.com','role' => 'Admin'],
            ['name' => 'User1','email' => 'user1@example.com','role' => 'Admin'],
            ['name' => 'User2','email' => 'user2@example.com','role' => 'Admin'],
            ['name' => 'User2','email' => 'user2@example.com','role' => 'Admin']
        ];
            
        $result = $getUsersMock->getAllGroupMembers('test_graph_url', ['test_group_id_1', 'test_group_id_2'], 'test_access_token');
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetUsersFromGroups()
    {
        Config::set('AZURE_CLIENT_ID', 'client_id');
        Config::set('AZURE_CLIENT_SECRET', 'client_secret');
        Config::set('TENANT', 'tenant_id');
        Config::set('GRAPH_URL', 'graph_url');
        Config::set('AZURE_GROUP_ID_CLIENTS', 'group_id_clients');
        Config::set('AZURE_GROUP_ID_ADMINS', 'group_id_admins');

        $getUsersMock = Mockery::mock(GetUsers::class)->makePartial();
        $getUsersMock->shouldReceive('getAccessToken')->andReturn('mocked_access_token');
        $getUsersMock->shouldReceive('getAllGroupMembers')->andReturn(['user1', 'user2', 'user3']);

        $result = $getUsersMock->getUsersFromGroups($getUsersMock);
        $this->assertSame($result, ['user1', 'user2', 'user3']);
    }
}