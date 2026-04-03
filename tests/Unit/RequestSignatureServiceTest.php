<?php

namespace Tests\Unit;

use App\Services\RequestSignatureService;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequestSignatureServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('game.signature.secret', 'test-signature-secret');
        config()->set('game.signature.nonce_store', 'array');
        config()->set('game.signature.max_skew_seconds', 60);
    }

    public function test_it_extracts_missing_headers_using_configured_header_names(): void
    {
        $headers = app(RequestSignatureService::class)->extractHeaders(Request::create('/api/game/session/start', 'POST'));

        $this->assertSame(['X-Timestamp', 'X-Nonce', 'X-Signature'], app(RequestSignatureService::class)->missingHeaders($headers));
    }

    public function test_it_validates_timestamp_freshness(): void
    {
        $service = app(RequestSignatureService::class);

        $this->assertTrue($service->isFreshTimestamp((string) now()->timestamp));
        $this->assertFalse($service->isFreshTimestamp((string) now()->subMinutes(5)->timestamp));
        $this->assertFalse($service->isFreshTimestamp('not-a-timestamp'));
    }

    public function test_it_signs_and_validates_request_payloads_consistently(): void
    {
        $service = app(RequestSignatureService::class);
        $timestamp = (string) now()->timestamp;
        $nonce = 'signature-service-nonce';
        $body = json_encode(['score' => 250], JSON_THROW_ON_ERROR);

        $signature = $service->sign('POST', '/api/game/submit-score', $body, $timestamp, $nonce);

        $request = Request::create('/api/game/submit-score', 'POST', [], [], [], [], $body);

        $this->assertTrue($service->hasConfiguredSecret());
        $this->assertTrue($service->hasValidSignature($request, $timestamp, $nonce, $signature));
        $this->assertFalse($service->hasValidSignature($request, $timestamp, $nonce, 'invalid-signature'));
    }
}
