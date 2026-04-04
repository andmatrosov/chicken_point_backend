<?php

namespace Tests\Unit;

use App\Support\CountryFlagHelper;
use Tests\TestCase;

class CountryFlagHelperTest extends TestCase
{
    public function test_it_converts_valid_country_codes_to_flag_emoji(): void
    {
        $this->assertSame('🇺🇸', CountryFlagHelper::fromCode('US'));
        $this->assertSame('🇬🇪', CountryFlagHelper::fromCode('ge'));
        $this->assertSame('🇩🇪', CountryFlagHelper::fromCode('De'));
    }

    public function test_it_returns_null_for_invalid_or_missing_codes(): void
    {
        $this->assertNull(CountryFlagHelper::fromCode(null));
        $this->assertNull(CountryFlagHelper::fromCode(''));
        $this->assertNull(CountryFlagHelper::fromCode('USA'));
        $this->assertNull(CountryFlagHelper::fromCode('1A'));
    }
}
