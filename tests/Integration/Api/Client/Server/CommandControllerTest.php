<?php

namespace App\Tests\Integration\Api\Client\Server;

use App\Http\Controllers\Api\Client\Servers\CommandController;
use App\Http\Requests\Api\Client\Servers\SendCommandRequest;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Response;
use App\Models\Permission;
use App\Repositories\Daemon\DaemonServerRepository;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use App\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommandControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a validation error is returned if there is no command present in the
     * request.
     */
    public function test_validation_error_is_returned_if_no_command_is_present(): void
    {
        [$user, $server] = $this->generateTestAccount();

        $response = $this->actingAs($user)->postJson("/api/client/servers/$server->uuid/command", [
            'command' => '',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.0.meta.rule', 'required');
    }

    /**
     * Test that a subuser without the required permission receives an error when trying to
     * execute the command.
     */
    public function test_subuser_without_permission_receives_error(): void
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);

        $response = $this->actingAs($user)->postJson("/api/client/servers/$server->uuid/command", [
            'command' => 'say Test',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test that a command can be sent to the server.
     */
    public function test_command_can_send_to_server(): void
    {
        $service = \Mockery::mock(DaemonServerRepository::class);
        $this->app->instance(DaemonServerRepository::class, $service);

        [$user, $server] = $this->generateTestAccount([Permission::ACTION_CONTROL_CONSOLE]);

        $service->expects('setServer')
            ->with(\Mockery::on(function ($value) use ($server) {
                return $server->uuid === $value->uuid;
            }))
            ->andReturnSelf()
            ->getMock()
            ->expects('command')
            ->with('say Test');

        $request = new SendCommandRequest(['command' => 'say Test']);
        $cc = resolve(CommandController::class);

        $response = $cc->index($request, $server);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * Test that an error is returned when the server is offline that is more specific than the
     * regular daemon connection error.
     */
    public function test_error_is_returned_when_server_is_offline(): void
    {
        $service = \Mockery::mock(DaemonServerRepository::class);
        $this->app->instance(DaemonServerRepository::class, $service);

        [$user, $server] = $this->generateTestAccount();

        $service->expects('setServer')
            ->with(\Mockery::on(function ($value) use ($server) {
                return $server->uuid === $value->uuid;
            }))
            ->andReturnSelf()
            ->getMock()
            ->expects('command')
            ->with('say Test')
            ->andThrows(new ConnectionException(previous: new BadResponseException('', new Request('GET', 'test'), new GuzzleResponse(Response::HTTP_BAD_GATEWAY))));

        $request = new SendCommandRequest(['command' => 'say Test']);
        $cc = resolve(CommandController::class);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessageMatches('/Server must be online in order to send commands\./');

        $response = $cc->index($request, $server);

        $this->assertEquals(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
    }
}
