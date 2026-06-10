<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller;

use App\Controller\GithubOauthController;
use App\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GithubOauthControllerTest extends TestCase
{
    public function testGetReposReturnsUnauthorizedWithoutToken(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getGithubToken')->willReturn(null);

        $controller = new class($user) extends GithubOauthController {
            private User $user;

            public function __construct(User $user)
            {
                $this->user = $user;
            }

            public function getUser(): ?User
            {
                return $this->user;
            }
        };

        $request = new Request();
        $response = $controller->getRepos($request);

        $this->assertSame(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testGetReposReturnsUnauthorizedWithEmptyToken(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getGithubToken')->willReturn('');

        $controller = new class($user) extends GithubOauthController {
            private User $user;

            public function __construct(User $user)
            {
                $this->user = $user;
            }

            public function getUser(): ?User
            {
                return $this->user;
            }
        };

        $request = new Request(['page' => -1, 'perPage' => 30]);
        $response = $controller->getRepos($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetFilesReturnsUnauthorizedWithoutUser(): void
    {
        $controller = new class extends GithubOauthController {
            public function getUser(): ?User
            {
                return null;
            }
        };

        $request = new Request();
        $response = $controller->getFiles($request);

        $this->assertSame(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testParseLinkExtractsLastPage(): void
    {
        $controller = new GithubOauthController();
        $method = new \ReflectionMethod($controller, 'parseLink');

        $link = '<https://api.github.com/repos?page=3>; rel="last", <https://api.github.com/repos?page=1>; rel="first"';
        $result = $method->invoke($controller, $link, 'last');

        $this->assertSame('3', $result);
    }

    public function testParseLinkReturnsNullWhenNoMatch(): void
    {
        $controller = new GithubOauthController();
        $method = new \ReflectionMethod($controller, 'parseLink');

        $result = $method->invoke($controller, '', 'last');
        $this->assertNull($result);

        $result = $method->invoke($controller, '<https://api.github.com/repos?page=1>; rel="first"', 'last');
        $this->assertNull($result);
    }
}
