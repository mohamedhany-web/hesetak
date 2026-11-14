<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * يثبت خوارزمية AES-256-CBC + hex التي يعتمدها NeoLeapService (بدون قاعدة بيانات).
 */
class NeoLeapEncryptionTest extends TestCase
{
    public function test_aes_256_cbc_hex_roundtrip_matches_neoleap_pattern(): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $plain = json_encode(['id' => 'portal', 'amt' => '10.000', 'action' => '1'], JSON_UNESCAPED_SLASHES);

        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $this->assertNotFalse($cipher);

        $hex = strtoupper(bin2hex($cipher));
        $this->assertTrue(ctype_xdigit($hex));

        $decoded = openssl_decrypt(hex2bin($hex), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $this->assertSame($plain, $decoded);
    }
}
