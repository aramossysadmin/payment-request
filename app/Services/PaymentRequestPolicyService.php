<?php

namespace App\Services;

use App\Models\PaymentRequestPolicy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class PaymentRequestPolicyService
{
    private const CACHE_KEY = 'payment_request_policy';

    private const CACHE_TTL_SECONDS = 60;

    public function __construct(public PaymentRequestPolicy $policy) {}

    public static function fromCurrent(): self
    {
        $policy = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => PaymentRequestPolicy::current()
        );

        return new self($policy);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ============================================================
    // VENTANA DE CAPTURA
    // ============================================================

    public function isCaptureWindowActive(): bool
    {
        return (bool) $this->policy->capture_window_is_active;
    }

    public function isWithinCaptureWindow(?CarbonInterface $now = null): bool
    {
        if (! $this->isCaptureWindowActive()) {
            return true; // ventana inactiva = siempre abierto
        }

        $now ??= CarbonImmutable::now(config('app.timezone'));

        // Snooze activo extiende la ventana hasta snoozed_until
        if ($this->policy->capture_window_snoozed_until
            && $now->lt($this->policy->capture_window_snoozed_until)) {
            return true;
        }

        return $this->isWithinWindow(
            $now,
            $this->policy->capture_open_day,
            $this->policy->capture_open_time,
            $this->policy->capture_close_day,
            $this->policy->capture_close_time,
        );
    }

    public function canCaptureNow(User $user, ?CarbonInterface $now = null): bool
    {
        return $this->userHasOverride($user) || $this->isWithinCaptureWindow($now);
    }

    public function getCurrentCaptureClosesAt(?CarbonInterface $ref = null): CarbonInterface
    {
        $ref ??= CarbonImmutable::now(config('app.timezone'));
        $closesAt = $this->resolveDayTime($ref, $this->policy->capture_close_day, $this->policy->capture_close_time, 'current');

        // Si el cierre de esta semana ya pasó, devolver el de la próxima semana.
        if ($closesAt->lt($ref)) {
            $closesAt = $closesAt->addWeek();
        }

        return $closesAt;
    }

    public function getNextCaptureOpensAt(?CarbonInterface $ref = null): CarbonInterface
    {
        $ref ??= CarbonImmutable::now(config('app.timezone'));
        $current = $this->resolveDayTime($ref, $this->policy->capture_open_day, $this->policy->capture_open_time, 'current');

        if ($current->isFuture()) {
            return $current;
        }

        return $this->resolveDayTime($ref, $this->policy->capture_open_day, $this->policy->capture_open_time, 'next');
    }

    // ============================================================
    // VENTANA DE SUBMIT
    // ============================================================

    public function isSubmitWindowActive(): bool
    {
        return (bool) $this->policy->submit_window_is_active;
    }

    public function isWithinSubmitWindow(?CarbonInterface $now = null): bool
    {
        if (! $this->isSubmitWindowActive()) {
            return true; // inactiva = siempre abierto (default actual)
        }

        $now ??= CarbonImmutable::now(config('app.timezone'));

        if ($this->policy->submit_window_snoozed_until
            && $now->lt($this->policy->submit_window_snoozed_until)) {
            return true;
        }

        return $this->isWithinWindow(
            $now,
            $this->policy->submit_open_day,
            $this->policy->submit_open_time,
            $this->policy->submit_close_day,
            $this->policy->submit_close_time,
        );
    }

    public function canSubmitNow(User $user, ?CarbonInterface $now = null): bool
    {
        return $this->userHasOverride($user) || $this->isWithinSubmitWindow($now);
    }

    public function getCurrentSubmitClosesAt(?CarbonInterface $ref = null): CarbonInterface
    {
        $ref ??= CarbonImmutable::now(config('app.timezone'));
        $closesAt = $this->resolveDayTime($ref, $this->policy->submit_close_day, $this->policy->submit_close_time, 'current');

        if ($closesAt->lt($ref)) {
            $closesAt = $closesAt->addWeek();
        }

        return $closesAt;
    }

    public function getNextSubmitOpensAt(?CarbonInterface $ref = null): CarbonInterface
    {
        $ref ??= CarbonImmutable::now(config('app.timezone'));
        $current = $this->resolveDayTime($ref, $this->policy->submit_open_day, $this->policy->submit_open_time, 'current');

        if ($current->isFuture()) {
            return $current;
        }

        return $this->resolveDayTime($ref, $this->policy->submit_open_day, $this->policy->submit_open_time, 'next');
    }

    // ============================================================
    // OVERRIDE / ROLES
    // ============================================================

    public function userHasOverride(User $user): bool
    {
        $roles = $this->policy->override_role_names ?? ['super_admin'];

        return $user->hasAnyRole($roles);
    }

    public function userCanEditPolicy(User $user): bool
    {
        $roles = $this->policy->editor_role_names ?? ['super_admin', 'project_manager'];

        return $user->hasAnyRole($roles);
    }

    // ============================================================
    // FECHA DE PROVISIÓN
    // ============================================================

    public function getNextProvisionDate(?CarbonInterface $ref = null): CarbonInterface
    {
        $ref ??= CarbonImmutable::now(config('app.timezone'));
        $offset = max(1, (int) $this->policy->provision_target_week_offset);

        // Tomamos el inicio de la semana ISO actual (lunes), avanzamos N semanas, llevamos al día objetivo.
        $startOfWeek = $ref->startOfWeek(CarbonImmutable::MONDAY);
        $targetWeek = $startOfWeek->addWeeks($offset);

        return $targetWeek->next($this->dayNameToConstant($this->policy->provision_target_day))
            ->startOfDay();
    }

    // ============================================================
    // PAYLOAD PARA FRONTEND
    // ============================================================

    public function buildFrontendPayload(User $user): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        $captureClosesAt = $this->getCurrentCaptureClosesAt($now);
        $captureOpensAt = $this->getNextCaptureOpensAt($now);
        $submitClosesAt = $this->isSubmitWindowActive() ? $this->getCurrentSubmitClosesAt($now) : null;
        $submitOpensAt = $this->isSubmitWindowActive() ? $this->getNextSubmitOpensAt($now) : null;
        $provisionDate = $this->getNextProvisionDate($now);

        return [
            'capture' => [
                'canAct' => $this->canCaptureNow($user, $now),
                'isWindowActive' => $this->isCaptureWindowActive(),
                'opensAt' => $captureOpensAt->toIso8601String(),
                'closesAt' => $captureClosesAt->toIso8601String(),
                'opensAtLabel' => $this->formatHuman($captureOpensAt),
                'closesAtLabel' => $this->formatHuman($captureClosesAt),
                'windowLabel' => $this->formatWindowLabel($captureOpensAt, $captureClosesAt),
            ],
            'submit' => [
                'canAct' => $this->canSubmitNow($user, $now),
                'isWindowActive' => $this->isSubmitWindowActive(),
                'opensAt' => $submitOpensAt?->toIso8601String(),
                'closesAt' => $submitClosesAt?->toIso8601String(),
                'opensAtLabel' => $submitOpensAt ? $this->formatHuman($submitOpensAt) : null,
                'closesAtLabel' => $submitClosesAt ? $this->formatHuman($submitClosesAt) : null,
                'windowLabel' => $this->isSubmitWindowActive()
                    ? $this->formatWindowLabel($submitOpensAt, $submitClosesAt)
                    : 'Siempre abierto',
            ],
            'provision' => [
                'nextDate' => $provisionDate->toDateString(),
                'label' => $provisionDate->locale('es_MX')->translatedFormat('l j \\d\\e F \\d\\e Y'),
            ],
            'warningMinutesBeforeClose' => $this->policy->warning_minutes_before_close,
            'isOverride' => $this->userHasOverride($user),
        ];
    }

    // ============================================================
    // HELPERS INTERNOS
    // ============================================================

    private function isWithinWindow(CarbonInterface $now, string $openDay, string $openTime, string $closeDay, string $closeTime): bool
    {
        $windowOpen = $this->resolveDayTime($now, $openDay, $openTime, 'current');
        $windowClose = $this->resolveDayTime($now, $closeDay, $closeTime, 'current');

        // Si el día de apertura es POSTERIOR al de cierre en la misma semana (ej. open=friday, close=monday),
        // tratamos la ventana como cross-week.
        if ($windowOpen->gt($windowClose)) {
            $windowClose = $windowClose->addWeek();
        }

        return $now->between($windowOpen, $windowClose);
    }

    private function resolveDayTime(CarbonInterface $ref, string $dayName, string $timeStr, string $direction): CarbonInterface
    {
        $startOfWeek = $ref->startOfWeek(CarbonImmutable::MONDAY);
        $targetDay = $this->dayNameToConstant($dayName);
        $time = $this->parseTime($timeStr);

        // Lunes=1...Domingo=7 en ISO; calcular offset desde lunes
        $offset = match ($targetDay) {
            CarbonImmutable::MONDAY => 0,
            CarbonImmutable::TUESDAY => 1,
            CarbonImmutable::WEDNESDAY => 2,
            CarbonImmutable::THURSDAY => 3,
            CarbonImmutable::FRIDAY => 4,
            CarbonImmutable::SATURDAY => 5,
            CarbonImmutable::SUNDAY => 6,
            default => 0,
        };

        $dayThisWeek = $startOfWeek->addDays($offset)
            ->setTime((int) $time['h'], (int) $time['m'], (int) $time['s']);

        if ($direction === 'next') {
            return $dayThisWeek->addWeek();
        }

        return $dayThisWeek;
    }

    private function dayNameToConstant(string $dayName): int
    {
        return match (strtolower($dayName)) {
            'monday' => CarbonImmutable::MONDAY,
            'tuesday' => CarbonImmutable::TUESDAY,
            'wednesday' => CarbonImmutable::WEDNESDAY,
            'thursday' => CarbonImmutable::THURSDAY,
            'friday' => CarbonImmutable::FRIDAY,
            'saturday' => CarbonImmutable::SATURDAY,
            'sunday' => CarbonImmutable::SUNDAY,
            default => CarbonImmutable::MONDAY,
        };
    }

    /**
     * @return array{h: string, m: string, s: string}
     */
    private function parseTime(string $time): array
    {
        $parts = explode(':', $time) + [0, 0, 0];

        return ['h' => $parts[0], 'm' => $parts[1], 's' => $parts[2] ?? 0];
    }

    private function formatHuman(CarbonInterface $dt): string
    {
        // Sin sufijo de TZ — el frontend agrega la conversión a hora local del navegador.
        return $dt->locale('es_MX')->translatedFormat('l j M H:i');
    }

    private function formatWindowLabel(CarbonInterface $opens, CarbonInterface $closes): string
    {
        $opensText = $opens->locale('es_MX')->translatedFormat('l j M H:i');
        $closesText = $closes->locale('es_MX')->translatedFormat('l j M H:i');

        return "{$opensText} — {$closesText}";
    }
}
