<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use DateTimeImmutable;
use DateTimeZone;
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

        return array_merge($row, [
            'type_label' => $this->labelForType($type),
            'summary' => $this->summaryFor($type, $status, $row),
            'created_at' => $this->formatTimestamp($created),
        ]);
    }

    public function labelForType(string $type): string
    {
        return match ($type) {
            'platform.install' => 'Установка модулей',
            'platform.upgrade' => 'Обновление платформы',
            'site.module.enable' => 'Включение модуля на сайте',
            'site.module.disable' => 'Отключение модуля на сайте',
            'core.upgrade' => 'Обновление ядра',
            default => '' !== $type ? $type : 'Операция',
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function summaryFor(string $type, string $status, array $row): string
    {
        $phase = isset($row['phase']) && is_string($row['phase']) && '' !== $row['phase'] ? $row['phase'] : null;
        $label = $this->labelForType($type);
        $statusRu = match ($status) {
            'completed', 'success' => 'завершена',
            'failed', 'error' => 'ошибка',
            'running', 'in_progress' => 'выполняется',
            'pending', 'queued' => 'в очереди',
            default => $status,
        };

        return null !== $phase
            ? sprintf('%s — %s (%s)', $label, $statusRu, $phase)
            : sprintf('%s — %s', $label, $statusRu);
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
