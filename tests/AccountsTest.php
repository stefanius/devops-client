<?php

namespace TestMonitor\DevOps\Tests;

use Mockery;
use GuzzleHttp\Psr7\Response;
use TestMonitor\DevOps\Client;
use PHPUnit\Framework\TestCase;
use TestMonitor\DevOps\Resources\Account;
use TestMonitor\DevOps\Resources\Profile;
use TestMonitor\DevOps\Exceptions\NotFoundException;
use TestMonitor\DevOps\Exceptions\ValidationException;
use TestMonitor\DevOps\Exceptions\FailedActionException;
use TestMonitor\DevOps\Exceptions\UnauthorizedException;

class AccountsTest extends TestCase
{
    protected $token;

    protected $account;

    protected $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = Mockery::mock('\TestMonitor\DevOps\AccessToken');
        $this->token->shouldReceive('expired')->andReturnFalse();

        $this->account = ['AccountId' => '1', 'AccountName' => 'Account'];
        $this->profile = ['id' => '1', 'displayName' => 'My Name', 'emailAddress' => 'me@devops.com'];
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function it_should_return_a_list_of_accounts()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($this->profile)));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([$this->account])));

        // When
        $accounts = $devops->accounts();

        // Then
        $this->assertIsArray($accounts);
        $this->assertCount(1, $accounts);
        $this->assertInstanceOf(Account::class, $accounts[0]);
        $this->assertEquals($this->account['AccountId'], $accounts[0]->id);
        $this->assertIsArray($accounts[0]->toArray());
    }

    /** @test */
    public function it_should_return_an_empty_list_of_accounts_there_are_no_available_organizations()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($this->profile)));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        // When
        $accounts = $devops->accounts();

        // Then
        $this->assertIsArray($accounts);
        $this->assertCount(0, $accounts);
    }

    /** @test */
    public function it_should_throw_a_failed_action_exception_when_client_receives_bad_request_while_getting_a_list_of_accounts()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, ['Content-Type' => 'application/json'], null));

        $this->expectException(FailedActionException::class);

        // When
        $devops->accounts();
    }

    /** @test */
    public function it_should_throw_a_notfound_exception_when_client_receives_not_found_while_getting_a_list_of_accounts()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(404, ['Content-Type' => 'application/json'], null));

        $this->expectException(NotFoundException::class);

        // When
        $devops->accounts();
    }

    /** @test */
    public function it_should_throw_a_unauthorized_exception_when_client_lacks_authorization_for_getting_a_list_of_accounts()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(401, ['Content-Type' => 'application/json'], null));

        $this->expectException(UnauthorizedException::class);

        // When
        $devops->accounts();
    }

    /** @test */
    public function it_should_throw_a_validation_exception_when_client_provides_invalid_data_while_getting_a_list_of_accounts()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(422, ['Content-Type' => 'application/json'], json_encode(['message' => 'invalid'])));

        $this->expectException(ValidationException::class);

        // When
        $devops->accounts();
    }

    /** @test */
    public function it_should_return_the_profile_of_the_current_authenticated_user()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($this->profile)));

        // When
        $profile = $devops->myself();

        // Then
        $this->assertIsObject($profile);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals($this->profile['id'], $profile->id);
    }

    /** @test */
    public function it_should_throw_a_failed_action_exception_when_client_receives_bad_request_while_getting_the_profile_of_the_current_authenticated_user()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, ['Content-Type' => 'application/json'], null));

        $this->expectException(FailedActionException::class);

        // When
        $devops->myself();
    }

    /** @test */
    public function it_should_throw_a_notfound_exception_when_client_receives_not_found_while_getting_the_profile_of_the_current_authenticated_user()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(404, ['Content-Type' => 'application/json'], null));

        $this->expectException(NotFoundException::class);

        // When
        $devops->myself();
    }

    /** @test */
    public function it_should_throw_a_unauthorized_exception_when_client_lacks_authorization_for_getting_the_profile_of_the_current_authenticated_user()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(401, ['Content-Type' => 'application/json'], null));

        $this->expectException(UnauthorizedException::class);

        // When
        $devops->myself();
    }

    /** @test */
    public function it_should_throw_a_validation_exception_when_client_provides_invalid_data_while_getting_the_profile_of_the_current_authenticated_user()
    {
        // Given
        $devops = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'appId' => 1, 'redirectUrl' => 'none'], 'myorg', $this->token);

        $devops->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')
            ->once()
            ->andReturn(new Response(422, ['Content-Type' => 'application/json'], json_encode(['message' => 'invalid'])));

        $this->expectException(ValidationException::class);

        // When
        $devops->myself();
    }
}
