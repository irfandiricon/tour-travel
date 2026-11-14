<?php

use App\Libraries\LoginThrottle;
use PHPUnit\Framework\TestCase;

/**
 * Validates: Requirements 3.3, 3.5
 *
 * @internal
 */
final class LoginThrottleTest extends TestCase
{
    private const NOW = 1_700_000_000;

    /** Apply n consecutive failures spaced 1s apart; return final state and last timestamp. */
    private function failTimes(int $n): array
    {
        $state = ['failed_attempts' => 0, 'locked_until' => null];
        $last  = null;
        $t     = self::NOW;
        for ($i = 0; $i < $n; $i++) {
            $state = LoginThrottle::registerFailure($state['failed_attempts'], $last, $t);
            $last  = $t;
            $t++;
        }

        return $state + ['last' => $last];
    }

    public function testFourFailuresDoNotLock(): void
    {
        $s = $this->failTimes(4);
        $this->assertSame(4, $s['failed_attempts']);
        $this->assertNull($s['locked_until']);
    }

    public function testFifthFailureLocksForFifteenMinutes(): void
    {
        $s = $this->failTimes(5);
        $this->assertSame(5, $s['failed_attempts']);
        $this->assertSame(
            date('Y-m-d H:i:s', $s['last'] + 900),
            $s['locked_until']
        );
    }

    public function testFailureAfterWindowExpiryRestartsCount(): void
    {
        $r = LoginThrottle::registerFailure(4, self::NOW, self::NOW + 900);
        $this->assertSame(1, $r['failed_attempts']);
        $this->assertNull($r['locked_until']);
    }

    public function testFailureJustInsideWindowKeepsCounting(): void
    {
        $r = LoginThrottle::registerFailure(4, self::NOW, self::NOW + 899);
        $this->assertSame(5, $r['failed_attempts']);
        $this->assertNotNull($r['locked_until']);
    }

    public function testSuccessResetsState(): void
    {
        $this->assertSame(
            ['failed_attempts' => 0, 'locked_until' => null],
            LoginThrottle::registerSuccess()
        );
    }

    public function testIsLockedBoundary(): void
    {
        $until = date('Y-m-d H:i:s', self::NOW + 900);
        $ts    = strtotime($until);

        $this->assertTrue(LoginThrottle::isLocked($until, $ts - 1));
        $this->assertFalse(LoginThrottle::isLocked($until, $ts));
        $this->assertFalse(LoginThrottle::isLocked($until, $ts + 1));
    }

    public function testIsLockedFalseForNullOrEmpty(): void
    {
        $this->assertFalse(LoginThrottle::isLocked(null, self::NOW));
        $this->assertFalse(LoginThrottle::isLocked('', self::NOW));
    }

    public function testGenericErrorMessageDoesNotRevealWhichFieldFailed(): void
    {
        $class = 'App\Controllers\Admin\AuthController';
        if (! class_exists($class) || ! defined($class . '::GENERIC_ERROR')) {
            $this->markTestSkipped('AuthController::GENERIC_ERROR is not implemented yet.');
        }

        $msg = strtolower((string) constant($class . '::GENERIC_ERROR'));
        $this->assertNotSame('', $msg);
        $this->assertDoesNotMatchRegularExpression('/(email|username|password)\s+(is\s+)?(incorrect|wrong|not found|invalid)/', $msg);
    }
}
