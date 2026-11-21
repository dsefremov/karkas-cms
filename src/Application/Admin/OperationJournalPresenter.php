<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use Throwable;

final class OperationJournalPresenter
{
    private const TZ = 'Europe/Moscow';

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function present(array $row): array
    {
        $type = (string) ($row['operation_type'] ?? '');
        $status = (string) ($row['status'] ?? '');
        $created = (string) ($row['created_at'] ?? '');
        $payload = $this->decodePayload($row['payload'] ?? null);

        return array_merge($row, [
            'type_label' => $this->labelForType($type),
            'status_label' => $this->labelForStatus($status),
            'summary' => $this->summaryFor($type, $status, $row, $payload),
            'created_at' => $this->formatTimestamp($created),
        ]);
    }

    public function labelForType(string $type): string
    {
        return match ($type) {
            'package.install' => 'Установка пакетов',
            'module.site_enable' => 'Включение модуля на сайте',
            'platform.rollback' => 'Откат обновления',
            'platform.install' => 'Установка пакетов',
            'platform.upgrade' => 'Обновление платформы',
            'installer.bootstrap' => 'Запуск установщика',
            'installer.complete' => 'Завершение установки',
            'blog.post.create' => 'Создание записи блога',
            'blog.post.save' => 'Сохранение записи',
            'site.create' => 'Создание сайта',
            'site.module.enable' => 'Включение модуля на сайте',
            'site.module.disable' => 'Отключение модуля на сайте',
            'blog.facade.bind' => 'Привязка блога к странице',
            'blog.facade.unbind' => 'Отвязка блога',
            'core.upgrade' => 'Обновление ядра',
            default => 'Служебная операция',
        };
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'succeeded', 'completed', 'success' => 'Завершена',
            'failed', 'error' => 'Ошибка',
            'running', 'in_progress' => 'Выполняется',
            'pending', 'queued' => 'В очереди',
            'rolled_back' => 'Откачена',
            default => 'Неизвестно',
        };
    }

    public function labelForPackage(string $packageId): string
    {
        return match ($packageId) {
            'karkas/core' => 'Ядро',
            'karkas/blog' => 'Блог',
            default => $packageId,
        };
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $payload
     */
    private function summaryFor(string $type, string $status, array $row, array $payload): string
    {
        $statusRu = mb_strtolower($this->labelForStatus($status));
        $details = match ($type) {
            'package.install' => $this->packagesSummary($payload),
            'module.site_enable' => $this->siteEnableSummary($payload),
            default => null,
        };
        $phase = isset($row['phase']) && is_string($row['phase']) ? $this->labelForPhase($row['phase']) : null;
        $parts = array_values(array_filter([$details, $statusRu], static fn (?string $part): bool => null !== $part && '' !== $part));
        $summary = implode(' — ', $parts);
        if (null !== $phase && !\in_array($status, ['succeeded', 'completed', 'success'], true)) {
            $summary .= sprintf(' (этап: %s)', $phase);
        }

        return $summary;
    }

    private function labelForPhase(string $phase): ?string
    {
        return match ($phase) {
            'precheck' => 'предварительная проверка',
            'maintenance' => 'режим обслуживания',
            'enable' => 'включение',
            'rollback' => 'откат',
            'error' => 'ошибка',
            'completed', '' => null,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function packagesSummary(array $payload): ?string
    {
        $packages = $this->packageIds($payload);
        if ([] === $packages) {
            return null;
        }

        return implode(', ', array_map(fn (string $id): string => $this->labelForPackage($id), $packages));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function siteEnableSummary(array $payload): ?string
    {
        $package = isset($payload['package_id']) && is_string($payload['package_id']) ? $this->labelForPackage($payload['package_id']) : null;
        $mount = isset($payload['mount']) && is_string($payload['mount']) ? $payload['mount'] : null;
        if (null === $package) {
            return null;
        }

        return null !== $mount ? sprintf('%s на адресе %s', $package, $mount) : $package;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private function packageIds(array $payload): array
    {
        $raw = $payload['packages'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, 'is_string'));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $raw): array
    {
        if (is_array($raw)) {
            /* @var array<string, mixed> $raw */
            return $raw;
        }
        if (!is_string($raw) || '' === $raw) {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function formatTimestamp(string $raw): string
    {
        if ('' === $raw) {
            return '—';
        }
        try {
            $dt = new DateTimeImmutable($raw);
        } catch (Throwable) {
            $stripped = preg_replace('/\.\d+/', '', $raw) ?? $raw;

            try {
                $dt = new DateTimeImmutable($stripped);
            } catch (Throwable) {
                return $raw;
            }
        }
        $tz = new DateTimeZone(self::TZ);
        $localized = $dt->setTimezone($tz);

        return $localized->format('d.m.Y, H:i').' МСК';
    }
}
