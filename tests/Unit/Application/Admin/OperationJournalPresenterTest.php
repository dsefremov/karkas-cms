<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application\Admin;

use Karkas\Application\Admin\OperationJournalPresenter;
use PHPUnit\Framework\TestCase;

final class OperationJournalPresenterTest extends TestCase
{
    public function testCorePackageInstallIsHumanReadableInMoscowTime(): void
    {
        $row = new OperationJournalPresenter()->present([
            'operation_type' => 'package.install',
            'status' => 'succeeded',
            'phase' => 'completed',
            'payload' => '{"packages": ["karkas/core"]}',
            'created_at' => '2026-11-20 21:46:21.000000',
        ]);

        self::assertSame('Установка пакетов', $row['type_label']);
        self::assertSame('Завершена', $row['status_label']);
        self::assertSame('Ядро — завершена', $row['summary']);
        self::assertSame('21.11.2026, 00:46 МСК', $row['created_at']);
    }

    public function testModuleEnableAndInstallAreHumanReadable(): void
    {
        $presenter = new OperationJournalPresenter();
        $enable = $presenter->present([
            'operation_type' => 'module.site_enable',
            'status' => 'running',
            'phase' => 'enable',
            'payload' => '{"mount": "/blog", "package_id": "karkas/blog"}',
            'created_at' => '',
        ]);
        self::assertSame('Включение модуля на сайте', $enable['type_label']);
        self::assertSame('Блог на адресе /blog — выполняется (этап: включение)', $enable['summary']);

        $install = $presenter->present([
            'operation_type' => 'package.install',
            'status' => 'failed',
            'phase' => 'error',
            'payload' => '{"packages": ["karkas/blog"]}',
            'created_at' => '',
        ]);
        self::assertSame('Установка пакетов', $install['type_label']);
        self::assertSame('Ошибка', $install['status_label']);
    }

    public function testUnknownTypeNeverLeaksRawIdentifier(): void
    {
        $row = new OperationJournalPresenter()->present([
            'operation_type' => 'some.internal_type',
            'status' => 'weird',
            'created_at' => '',
        ]);
        self::assertSame('Служебная операция', $row['type_label']);
        self::assertStringNotContainsString('internal', (string) $row['summary']);
    }
}
