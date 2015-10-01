<?php

/*
 * This file is part of jwt-auth
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tymon\JWTAuth\Test;

use Mockery;
use Tymon\JWTAuth\Payload;
use Tymon\JWTAuth\Factory;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Claims\Issuer;
use Tymon\JWTAuth\Claims\IssuedAt;
use Tymon\JWTAuth\Claims\Expiration;
use Tymon\JWTAuth\Claims\NotBefore;
use Tymon\JWTAuth\Claims\Audience;
use Tymon\JWTAuth\Claims\Subject;
use Tymon\JWTAuth\Claims\JwtId;
use Tymon\JWTAuth\Claims\Custom;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->claimFactory = Mockery::mock('Tymon\JWTAuth\Claims\Factory');
        $this->validator = Mockery::mock('Tymon\JWTAuth\Validators\PayloadValidator');
        $this->factory = new Factory($this->claimFactory, Request::create('/foo', 'GET'), $this->validator);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    public function it_should_return_a_payload_when_passing_an_array_of_claims_to_make_method()
    {
        $this->validator->shouldReceive('setRefreshFlow->check');

        $expTime = time() + 3600;

        $this->claimFactory->shouldReceive('get')->once()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->once()->with('iss', Mockery::any())->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('iat', 123)->andReturn(new IssuedAt(123));
        $this->claimFactory->shouldReceive('get')->once()->with('jti', 'foo')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('nbf', 123)->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('get')->once()->with('exp', $expTime)->andReturn(new Expiration($expTime));

        $payload = $this->factory->claims(['sub' => 1, 'jti' => 'foo', 'iat' => 123, 'nbf' => 123])->make();

        $this->assertEquals($payload->get('sub'), 1);
        $this->assertEquals($payload->get('iat'), 123);
        $this->assertEquals($payload['exp'], $expTime);

        $this->assertInstanceOf('Tymon\JWTAuth\Payload', $payload);
    }

    /** @test */
    public function it_should_return_a_payload_when_chaining_claim_methods()
    {
        $this->validator->shouldReceive('setRefreshFlow->check');

        $this->claimFactory->shouldReceive('get')->once()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->once()->with('iss', Mockery::any())->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('exp', time() + 3600)->andReturn(new Expiration(time() + 3600));
        $this->claimFactory->shouldReceive('get')->once()->with('iat', time())->andReturn(new IssuedAt(time()));
        $this->claimFactory->shouldReceive('get')->once()->with('jti', Mockery::any())->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('nbf', time())->andReturn(new NotBefore(time()));
        $this->claimFactory->shouldReceive('get')->once()->with('foo', 'baz')->andReturn(new Custom('foo', 'baz'));

        $payload = $this->factory->sub(1)->foo('baz')->make();

        $this->assertEquals($payload['sub'], 1);
        $this->assertEquals($payload->get('jti'), 'foo');
        $this->assertEquals($payload->get('foo'), 'baz');

        $this->assertInstanceOf('Tymon\JWTAuth\Payload', $payload);
    }

    /** @test */
    public function it_should_return_a_payload_when_passing_miltidimensional_array_as_custom_claim_to_make_method()
    {
        $this->validator->shouldReceive('setRefreshFlow->check');

        $this->claimFactory->shouldReceive('get')->once()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->once()->with('iss', Mockery::any())->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('exp', Mockery::any())->andReturn(new Expiration(time() + 3600));
        $this->claimFactory->shouldReceive('get')->once()->with('iat', Mockery::any())->andReturn(new IssuedAt(time()));
        $this->claimFactory->shouldReceive('get')->once()->with('jti', Mockery::any())->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('get')->once()->with('nbf', Mockery::any())->andReturn(new NotBefore(time()));
        $this->claimFactory->shouldReceive('get')->once()->with('foo', ['bar' => [0, 0, 0]])->andReturn(new Custom('foo', ['bar' => [0, 0, 0]]));

        $payload = $this->factory->sub(1)->foo(['bar' => [0, 0, 0]])->make();

        $this->assertEquals($payload->get('sub'), 1);
        $this->assertEquals($payload->get('foo'), ['bar' => [0, 0, 0]]);
        $this->assertEquals($payload->get('foo.bar'), [0, 0, 0]);

        $this->assertInstanceOf('Tymon\JWTAuth\Payload', $payload);
    }

    /** @test */
    public function it_should_set_the_ttl()
    {
        $this->factory->setTTL(12345);

        $this->assertEquals($this->factory->getTTL(), 12345);
    }

    /** @test */
    public function it_should_set_the_default_claims()
    {
        $this->factory->setDefaultClaims(['sub', 'iat']);

        $this->assertEquals($this->factory->getDefaultClaims(), ['sub', 'iat']);
    }

    /** @test */
    public function it_should_get_the_validator()
    {
        $this->assertInstanceOf('Tymon\JWTAuth\Validators\PayloadValidator', $this->factory->validator());
    }

    /** @test */
    public function it_should_return_numeric_values_according_to_specs()
    {
        $this->assertInternalType('int', $this->factory->exp());
        $this->assertInternalType('int', $this->factory->nbf());
        $this->assertInternalType('int', $this->factory->iat());
    }
}
