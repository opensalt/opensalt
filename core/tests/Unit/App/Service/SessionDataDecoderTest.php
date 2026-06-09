<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\SessionDataDecoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class SessionDataDecoderTest extends TestCase
{
    private SessionDataDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new SessionDataDecoder();
    }

    public function testDecodeEmptySessionData(): void
    {
        $this->assertSame([], $this->decoder->decodeSessionData(''));
    }

    public function testDecodeSingleKey(): void
    {
        $data = 'testkey|' . serialize('testvalue');
        $result = $this->decoder->decodeSessionData($data);
        $this->assertSame(['testkey' => 'testvalue'], $result);
    }

    public function testDecodeMultipleKeys(): void
    {
        $data = 'key1|' . serialize('val1') . 'key2|' . serialize('val2');
        $result = $this->decoder->decodeSessionData($data);
        $this->assertSame(['key1' => 'val1', 'key2' => 'val2'], $result);
    }

    public function testDecodeBooleanFalse(): void
    {
        $data = 'flag|' . serialize(false);
        $result = $this->decoder->decodeSessionData($data);
        $this->assertSame(['flag' => false], $result);
    }

    public function testDecodeSessionDoesNotInstantiateObjects(): void
    {
        $payload = 'evil|' . serialize(new \stdClass());
        $result = $this->decoder->decodeSessionData($payload);
        $this->assertArrayHasKey('evil', $result);
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $result['evil']);
    }

    public function testDecodeMalformedDataReturnsPartial(): void
    {
        $data = 'good|' . serialize('value') . 'BROKEN_DATA';
        $result = $this->decoder->decodeSessionData($data);
        $this->assertSame(['good' => 'value'], $result);
    }

    public function testDecodeSecurityTokenWithValidToken(): void
    {
        $user = new InMemoryUser('testuser', null, ['ROLE_USER']);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $serialized = serialize($token);

        $result = $this->decoder->decodeSecurityToken($serialized);
        $this->assertInstanceOf(UsernamePasswordToken::class, $result);
        $this->assertSame('testuser', $result->getUserIdentifier());
    }

    public function testDecodeSecurityTokenRejectsArbitraryObject(): void
    {
        $serialized = serialize(new \stdClass());
        $this->expectException(\InvalidArgumentException::class);
        $this->decoder->decodeSecurityToken($serialized);
    }

    public function testDecodeSecurityTokenRejectsNonTokenClass(): void
    {
        $serialized = serialize(new \ArrayObject());
        $this->expectException(\InvalidArgumentException::class);
        $this->decoder->decodeSecurityToken($serialized);
    }

    public function testDecodeSecurityTokenWithPostAuthenticationTokenAndInMemoryUser(): void
    {
        $user = new InMemoryUser('testuser', null, ['ROLE_USER']);
        $token = new PostAuthenticationToken($user, 'main', ['ROLE_USER']);
        $serialized = serialize($token);

        $result = $this->decoder->decodeSecurityToken($serialized);
        $this->assertInstanceOf(PostAuthenticationToken::class, $result);
        $this->assertSame('testuser', $result->getUserIdentifier());
    }

    public function testDecodeSecurityTokenRejectsTokenWithDisallowedEmbeddedClass(): void
    {
        // Create a token-like serialized string that embeds a class NOT in the allowlist
        $serialized = 'O:75:"Symfony\\Component\\Security\\Http\\Authenticator\\Token\\PostAuthenticationToken":2:{i:0;s:4:"main";i:1;a:5:{i:0;O:8:"stdClass":0:{}i:1;b:1;i:2;N;i:3;a:0:{}i:4;a:0:{}}}';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid authentication token in session.');
        $this->decoder->decodeSecurityToken($serialized);
    }
}
