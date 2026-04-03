<?php

namespace Tests\Unit;

use App\Services\DeploymentSafetyService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\TestCase;

class DeploymentSafetyServiceTest extends TestCase
{
    public function test_it_throws_when_debug_is_enabled_in_production(): void
    {
        config()->set('app.debug', true);
        config()->set('app.url', 'https://game.test');
        config()->set('app.key', 'base64:test-key');
        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'signature-secret');

        $service = new DeploymentSafetyService(
            $this->productionApplicationMock(),
            $this->createMock(UrlGenerator::class),
        );

        $this->expectException(RuntimeException::class);

        $service->enforce();
    }

    public function test_it_throws_when_app_url_is_not_https_in_production(): void
    {
        config()->set('app.debug', false);
        config()->set('app.url', 'http://game.test');
        config()->set('app.key', 'base64:test-key');
        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'signature-secret');

        $service = new DeploymentSafetyService(
            $this->productionApplicationMock(),
            $this->createMock(UrlGenerator::class),
        );

        $this->expectException(RuntimeException::class);

        $service->enforce();
    }

    public function test_it_throws_when_required_security_secret_is_missing_in_production(): void
    {
        config()->set('app.debug', false);
        config()->set('app.url', 'https://game.test');
        config()->set('app.key', 'base64:test-key');
        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', null);

        $service = new DeploymentSafetyService(
            $this->productionApplicationMock(),
            $this->createMock(UrlGenerator::class),
        );

        $this->expectException(RuntimeException::class);

        $service->enforce();
    }

    public function test_it_forces_https_scheme_for_valid_production_configuration(): void
    {
        config()->set('app.debug', false);
        config()->set('app.url', 'https://game.test');
        config()->set('app.key', 'base64:test-key');
        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'signature-secret');

        /** @var UrlGenerator&MockObject $url */
        $url = $this->createMock(UrlGenerator::class);
        $url->expects($this->once())
            ->method('forceScheme')
            ->with('https');

        $service = new DeploymentSafetyService(
            $this->productionApplicationMock(),
            $url,
        );

        $service->enforce();
    }

    protected function productionApplicationMock(): Application&MockObject
    {
        /** @var Application&MockObject $application */
        $application = $this->createMock(Application::class);
        $application->method('environment')->with('production')->willReturn(true);

        return $application;
    }
}
