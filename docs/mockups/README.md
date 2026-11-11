# Каркас · Karkas CMS — статичные макеты

Новая поставка · 7 ноября 2026, МСК (UTC+3). Продукт называется «Каркас». Публичный блог — клиентский «Журнал», а не бренд CMS.

96 статичных HTML-экранов и 101 PNG: 96 desktop 1440px + 5 mobile 390px.

## Текущий scope
**v1: блог с поиском и кабинетом Автора на ОДНОМ сайте.** Установка, пакеты с проверкой архива, обслуживающие обновления и служебная 2FA входят в показанный запуск. Новые экраны не содержат переключателя сайтов.

**Очередь I по v0.8 также сохраняет минимальные generic pages / PageTree и basic mounts/PageBinding.** Новых редакторов страниц не добавляли; существующие tree/tree-move и basic binding/unbind возвращены в v1, без выбора сайта.

**Pages / дерево страниц входят в v1. Вторая очередь:** multisite, Landings / Services и сложные template rules. Их новые редакторы не рисовали. Прежние концепты платформы, включая sites/site-add/site-add-path/site-templates/templates и сложные module/site концепты, сохранены, но помечены «Вторая очередь» в index.html, manifest.json и на самих экранах. Они не расширяют v1. Список отложенных экранов — `manifest.json`, поле `phase`.

## Статьи
- Согласования нет. Автор создаёт, редактирует и активирует свои статьи; Редактор и Админ — любые в разрешённом контексте.
- Только ручное «Сохранить»; последние 10 версий. Сохранение без изменений заблокировано и не создаёт версию. Индикатор несохранённых изменений рядом с Save.
- Новая статья Неактивна. Активность и slug применяются обычным Save; 301 GET/HEAD создаётся, если прежний адрес был публичным. Неактивный непубличный адрес просто меняется.
- Активность и slug хранятся в снимке версии, но помечены «не восстанавливается». Восстановление создаёт новую версию содержания с текущими активностью/адресом; активное содержание сразу live.
- История неизменяема. Невалидные ссылки/медиа/категории исправляются через текущий редактор и новую ручную версию. Частичного восстановления нет.
- Создана/Изменена/кем — МСК. Удаление используемой категории запрещено, включая неактивные статьи.
- Уход внутри приложения — модальный диалог. При закрытии вкладки браузер может показать стандартный диалог, но может и не показать: best effort, без гарантии.

## Установщик · install/
Среда и права → MySQL/FULLTEXT → сайт и первый Админ → SMTP (можно пропустить, тест отправки) → cron → миграции → готово. Ошибки окружения, БД, данных, SMTP, cron и миграций показаны отдельными экранами. После завершения — «Установщик закрыт», повторный URL locked без возможности переустановить или изменить первого Админа.

PHP 8.4+, MySQL 8.0/8.4 приведены как проектные требования; MySQL 8.0 допустима только с ранее согласованным EOL-исключением. Реальные хостинг и версии здесь не проверялись. Cron line, пути, лимиты и версии пакетов — демонстрационные; точные значения потребуют конфигурации окружения.

## Модули и проверка архива
Два пути: ZIP из админки или полная папка через файловый менеджер. Проверяются формат ZIP (для загрузки), manifest/schema, совместимые версии, безопасные пути: запрет ../, абсолютных путей, symlink и файлов вне папки модуля. Лимиты распаковки защищают от ZIP-bomb; проверки выполняются до запуска кода, staging закрытый. Папка через файловый менеджер не обходит manifest/path/version checks.

Проверка архива не гарантирует безопасность PHP-кода: оператор выбирает источник, которому доверяет.

ZIP → пароль + 2FA → проверка → состав/разрешения/подтверждение → установка. Повторное подтверждение остаётся обязательным, поскольку установка запускает сторонний код.

Статусы: доставлен, обнаружен/не установлен, не прошёл проверку архива (с причиной), отсутствует при сохранённых данных, файлы изменены. Отсутствие папки не удаляет данные. Ошибки: небезопасные пути, нет манифеста, несовместимая версия. При отключённой ZIP-загрузке используйте папку через файловый менеджер.

### Версии модулей и ядра
Списки modules и modules-available показывают текущую и доступную версии, badge обновления. Блог: 1.4.2 → 1.5.0, changelog и требования Core/PHP/зависимостей. Incompatible показывает требуемую и установленную версии Core и другого модуля; старт заблокирован.

Core: текущая 1.8.0, доступная 1.9.0. Compatibility table: OK / нужно обновить / несовместимо. Сначала устраните конфликты; зависимости не обновляются молча. Core-update-ready — альтернативное состояние после обновления зависимостей и повторной аутентификации. Затем maintenance/freeze/drain → согласованный backup → код/миграции → health check, done или проверенный rollback. Версия ядра видна в footer админки; на done она 1.9.0, на исходных экранах 1.8.0.

Maintenance блокирует новые изменения, посетители видят 503. Rollback допустим только для проверенного совместимого плана; произвольный downgrade БД не обещается. При необратимых миграциях нужен отдельный восстановительный план до старта.

## Служебный доступ
Отдельный контур от публичного Автора: логин → TOTP или одноразовый резервный код. Есть setup с НЕРАБОЧИМ QR-placeholder и демонстрационными резервными кодами, ошибка входа, серверная блокировка попыток, re-auth modal для опасных операций. Повторное подтверждение привязано к конкретной операции и пакету; истечение срока или ошибка не запускают действие.

Никаких реальных паролей, TOTP-секретов, QR, отправленных писем, установки модулей или запросов к БД здесь нет. Поля и кнопки — статичные примеры. MFA/проверка архива/rollback/защита архива должны быть реализованы и проверены отдельно.

## Файлы
`index.html` — навигатор; `manifest.json` — полный состав и scope; `tokens.css` — общая дизайн-система. `admin/`, `public/`, `install/` — готовый HTML; `png/` — полные снимки; assets — локальные обложки. `build.py`, `build-extra.py`, `editorial-components.py`, `launch-components.py`, `v13-components.py` — генераторы.

Контактные листы `admin.png`, `public.png`, `admin-modules.png`, `admin-sites.png`; дополнительные installer/modules/auth JPG в /workspace/cms_v15_*_sheet.jpg. Превью каждого нового экрана /workspace/cms_v15_<раздел>_<имя>.jpg. Архив /workspace/cms-mockups.zip.

## PNG

Автоматически сформировано из render-report.json.

| Экран | Ширина | PNG |
|---|---|---|
| Главная / лента | 1440 | `png/public/home-1440.png` |
| Главная / лента | 390 | `png/public/home-390.png` |
| Страница записи | 1440 | `png/public/post-1440.png` |
| Страница записи | 390 | `png/public/post-390.png` |
| Категория | 1440 | `png/public/category-1440.png` |
| Категория | 390 | `png/public/category-390.png` |
| Поиск | 1440 | `png/public/search-1440.png` |
| Поиск | 390 | `png/public/search-390.png` |
| Вложенная страница | 1440 | `png/public/page-1440.png` |
| Вход | 1440 | `png/public/login-1440.png` |
| Вход | 390 | `png/public/login-390.png` |
| Регистрация | 1440 | `png/public/register-1440.png` |
| Личный кабинет | 1440 | `png/public/account-1440.png` |
| Восстановление доступа | 1440 | `png/public/reset-1440.png` |
| Ожидание подтверждения | 1440 | `png/public/verify-1440.png` |
| Кабинет автора | 1440 | `png/public/author-workspace-1440.png` |
| Записи | 1440 | `png/admin/posts-1440.png` |
| Обзор | 1440 | `png/admin/dashboard-1440.png` |
| Редактор записи | 1440 | `png/admin/editor-1440.png` |
| Автор: редактор статьи | 1440 | `png/public/author-editor-1440.png` |
| История статьи | 1440 | `png/admin/post-history-1440.png` |
| Подтверждение восстановления | 1440 | `png/admin/post-history-restore-1440.png` |
| Автор: история статьи | 1440 | `png/public/author-history-1440.png` |
| Автор: подтверждение восстановления | 1440 | `png/public/author-history-restore-1440.png` |
| Новая неактивная статья | 1440 | `png/admin/editor-new-1440.png` |
| Восстановление невозможно | 1440 | `png/admin/post-history-restore-error-1440.png` |
| Статью изменили, пока вы смотрели | 1440 | `png/admin/post-history-restore-conflict-1440.png` |
| Сохранение без изменений | 1440 | `png/admin/editor-unchanged-1440.png` |
| Уход без сохранения | 1440 | `png/admin/editor-leave-1440.png` |
| Автор: новая неактивная статья | 1440 | `png/public/author-editor-new-1440.png` |
| Автор: Восстановление невозможно | 1440 | `png/public/author-history-restore-error-1440.png` |
| Автор: Статью изменили, пока вы смотрели | 1440 | `png/public/author-history-restore-conflict-1440.png` |
| Автор: Сохранение без изменений | 1440 | `png/public/author-editor-unchanged-1440.png` |
| Автор: Уход без сохранения | 1440 | `png/public/author-editor-leave-1440.png` |
| Категории | 1440 | `png/admin/categories-1440.png` |
| Дерево страниц | 1440 | `png/admin/tree-1440.png` |
| Перемещение страницы | 1440 | `png/admin/tree-move-1440.png` |
| Пользователи | 1440 | `png/admin/users-1440.png` |
| Роли и права | 1440 | `png/admin/roles-1440.png` |
| Настройки | 1440 | `png/admin/settings-1440.png` |
| Глобальный uninstall | 1440 | `png/admin/module-delete-1440.png` |
| Отдельная очистка данных | 1440 | `png/admin/module-delete-data-1440.png` |
| Убрать модуль с сайта | 1440 | `png/admin/module-site-remove-1440.png` |
| Lifecycle-операция | 1440 | `png/admin/module-operation-1440.png` |
| Настройки модуля Блог | 1440 | `png/admin/module-settings-1440.png` |
| Привязка к странице | 1440 | `png/admin/module-binding-1440.png` |
| Конфликт адреса | 1440 | `png/admin/module-conflict-1440.png` |
| Смена адреса и редиректы | 1440 | `png/admin/module-address-change-1440.png` |
| Отвязка: исход не выбран | 1440 | `png/admin/module-unbind-1440.png` |
| Отвязка: исход выбран | 1440 | `png/admin/module-unbind-ready-1440.png` |
| Сайты и шаблоны | 1440 | `png/admin/sites-1440.png` |
| Сайт на домене | 1440 | `png/admin/site-add-1440.png` |
| Сайт в базовом пути | 1440 | `png/admin/site-add-path-1440.png` |
| Правила шаблонов | 1440 | `png/admin/site-templates-1440.png` |
| Шаблоны | 1440 | `png/admin/templates-1440.png` |
| Защищённый старт установки | 1440 | `png/install/bootstrap-1440.png` |
| Проверка окружения | 1440 | `png/install/requirements-1440.png` |
| Окружение не готово | 1440 | `png/install/requirements-error-1440.png` |
| Подключение к базе | 1440 | `png/install/database-1440.png` |
| База данных: ошибки проверки | 1440 | `png/install/database-error-1440.png` |
| Сайт и первый Админ | 1440 | `png/install/site-admin-1440.png` |
| Проверьте данные сайта | 1440 | `png/install/site-admin-error-1440.png` |
| Почта · необязательный шаг | 1440 | `png/install/smtp-1440.png` |
| Тест SMTP не выполнен | 1440 | `png/install/smtp-error-1440.png` |
| Фоновые задачи | 1440 | `png/install/cron-1440.png` |
| Cron пока не обнаружен | 1440 | `png/install/cron-error-1440.png` |
| Подготовка базы · 65% | 1440 | `png/install/migrations-1440.png` |
| Миграции остановлены | 1440 | `png/install/migrations-error-1440.png` |
| Каркас установлен | 1440 | `png/install/done-1440.png` |
| Установщик закрыт | 1440 | `png/install/locked-1440.png` |
| Модули | 1440 | `png/admin/modules-1440.png` |
| Доступные пакеты | 1440 | `png/admin/modules-available-1440.png` |
| Загрузить модуль | 1440 | `png/admin/module-upload-1440.png` |
| Подтвердите установку | 1440 | `png/admin/module-upload-reauth-1440.png` |
| Проверка ZIP-пакета | 1440 | `png/admin/module-upload-verify-1440.png` |
| Установить «Галерея»? | 1440 | `png/admin/module-upload-confirm-1440.png` |
| Установка Галереи · 65% | 1440 | `png/admin/module-install-progress-1440.png` |
| Галерея установлена | 1440 | `png/admin/module-install-done-1440.png` |
| Пакет отклонён | 1440 | `png/admin/module-upload-error-1440.png` |
| Загрузка ZIP выключена на сервере | 1440 | `png/admin/module-upload-disabled-1440.png` |
| Обновление Блога · 1.4.2 → 1.5.0 | 1440 | `png/admin/module-update-confirm-1440.png` |
| Обновление · сайт на обслуживании | 1440 | `png/admin/module-update-progress-1440.png` |
| Откат к одной сохранённой версии завершён | 1440 | `png/admin/module-update-rollback-1440.png` |
| Обслуживание | 1440 | `png/public/maintenance-1440.png` |
| Вход в админку | 1440 | `png/admin/staff-login-1440.png` |
| Введите код 2FA | 1440 | `png/admin/staff-2fa-1440.png` |
| Настройка двухфакторного входа | 1440 | `png/admin/staff-2fa-setup-1440.png` |
| Не удалось войти | 1440 | `png/admin/staff-auth-error-1440.png` |
| Вход ограничен | 1440 | `png/admin/staff-locked-1440.png` |
| Повторное подтверждение | 1440 | `png/admin/staff-reauth-1440.png` |
| Обновление модуля заблокировано | 1440 | `png/admin/module-update-incompatible-1440.png` |
| Обновление ядра | 1440 | `png/admin/core-update-1440.png` |
| Ядро готово к обновлению | 1440 | `png/admin/core-update-ready-1440.png` |
| Обновление ядра · 60% | 1440 | `png/admin/core-update-progress-1440.png` |
| Ядро обновлено | 1440 | `png/admin/core-update-done-1440.png` |
| Откат Core к одной сохранённой версии завершён | 1440 | `png/admin/core-update-rollback-1440.png` |
| Модуль Блог · восстановление из копии | 1440 | `png/admin/module-update-restore-required-1440.png` |
| Модуль Блог · продолжение обновления | 1440 | `png/admin/module-update-resume-1440.png` |
| Ядро · восстановление из копии | 1440 | `png/admin/core-update-restore-required-1440.png` |
| Ядро · продолжение обновления | 1440 | `png/admin/core-update-resume-1440.png` |
| Старый пакет отклонён | 1440 | `png/admin/module-downgrade-blocked-1440.png` |

## Проверка и просмотр
Счётчики и inventory обновляет `python3 sync-readme.py`; validate.cjs сверяет их с manifest/render-report. Проверки охватывают local links, отсутствие старого бренда, scope phase 2, отсутствие site switcher на новых экранах, авторский UX, историю/restore, геометрию диалогов, служебный вход и пакеты с проверкой архива. Это проверки статичных макетов, не runtime/security/WCAG сертификация.

## Повторная генерация
`python3 build.py`; затем `PLAYWRIGHT_MODULE=/workspace/cms-render-tools/node_modules/playwright node render.cjs`; `python3 sync-readme.py`; с тем же PLAYWRIGHT_MODULE `node validate.cjs`; `python3 contact-sheet.py`; `python3 launch-previews.py`. Chrome путь можно задать CHROME_PATH. Без сети, сервера и секретов.

## Архитектура v0.8 · 20a · сверка v14
Прочитан актуальный раздел 20a через ReadCanvas: https://cursor.com/canvas/store-34a990a4-0955-48e5-847e-c1a12d1fe6d0/52e2b9d5-6a7e-45e2-8e30-1831acc3da98 . Прежние source-unknown/import-record трактовки отменены.

### ZIP по server file
«Выключено по умолчанию; включается файлом на сервере». Рекомендуемый ключ v0.8 — module_zip_upload_enabled, default false. Путь задаёт env CMS_UPLOAD_POLICY_FILE: абсолютный private path вне web root. Формат файла: {"module_zip_upload_enabled":true}. Отсутствующий файл, ошибка чтения/JSON или false → ZIP disabled. /home/account/private/cms-upload-policy.json — только пример, не фиксированный путь Timeweb.

UI только read-only effective status/instruction: нет checkbox, toggle, write endpoint или настройки БД для включения ZIP. True дополнительно требует SystemAdmin executable-code capability, повторную auth+MFA/CSRF и environment readiness. OFF даёт отказ на уровне приложения; остановка передачи тела HTTP не гарантируется. Flag/context повторно проверяются перед extraction/исполняемым шагом и перед активацией. После начала DDL не force kill, а безопасный checkpoint/maintenance/recovery. Folder incoming/READY под ZIP flag не попадает; файловый канал UI MFA не покрывает. На modules показано OFF; module-upload — отдельный пример готового ON после действия оператора на сервере.

### Доступная версия только из полного локального пакета
Источник I определён: полный локальный structurally validated ZIP или READY-folder. Отдельный manifest-record не является available candidate. Показаны target, candidate ID, digest, checkedAt, validation state; fetchedAt: N/A. Metadata untrusted, не гарантия безопасности кода. Без полного допустимого пакета доступная версия не определена. В I нет сетевой проверки доступных версий; интернет только во второй очереди. Никакого UI выбора источника.

### Ровно один rollback slot
У Core и каждого модуля ровно одна selectable saved KNOWN previous версия, с digest/generation и whole-set eligibility. Пример до update: Core current 1.8.0 / saved previous 1.7.0; Blog current 1.4.2 / saved previous 1.4.1. Успешное safe completion заменяет этот slot прежним known current; failure не затирает прежний slot. Pinned operation base, pending/staging/draining files не дополнительные версии для выбора.

Rollback-result — отдельное code rollback действие к единственному saved target после завершённого update: совместимость текущих schema/settings/task payloads проверена, БД/файлы из backup не восстанавливались. В показанной recommended consume policy использованный slot становится пустым, redo не появляется. Backup restore — отдельный recovery plan с RPO/потерей новых данных, не второй slot и не обещание отмены несовместимых миграций. При несовместимости code rollback запрещён, maintenance сохраняется, требуется restore/roll-forward. Resume не запускает DDL повторно без introspection/checkpoint/lease checks.

### Старый пакет не rollback
Понижение версии старым ZIP или папкой запрещено: installed/attempted показаны и для Core, и для модулей. New admin/module-downgrade-blocked также показывает same version + different digest collision, build metadata not newer и prerelease blocked. Fresh install без current не ошибочно считается downgrade. Forced downgrade кнопки нет.

### Права PHP и basic address
Заявленные permissions — intended use, не enforced restriction. PHP технически имеет права shared UID и доступ к доступным БД/файлам/secrets; нельзя трактовать список как изоляцию. Re-auth подтверждает человека, не безопасность кода.

Basic module-address-change возвращён в I: один сайт, prefix/route impact, обязательные 301 GET/HEAD и default-layout readiness; без site selector и сложных template rules. PageTree/basic bindings остаются I; новые Pages/Landings/Services редакторы не рисовали.

## Проверенные расхождения и открытые детали
Текущему brief и обязательным исправлениям 20a соответствуем. Путь policy file задаётся env CMS_UPLOAD_POLICY_FILE; конкретный private path показан только как пример. Slot consume policy в архитектуре рекомендательная; она показана как выбранный вариант макета, не дополнительное решение Den. Source-local-only, defaultOFF owner-file, downgrade deny и один previous slot — уже принятые правила, не открытые вопросы.

Остались реализационные параметры: назначенный оператором абсолютный private path через CMS_UPLOAD_POLICY_FILE, численные package/path/time/disk/inode budgets, физическое размещение private staging/recovery runner, fixtures slot-coherence/stage-revalidation, OPcache/DDL compatibility, backup retention/RPO/RTO и operator assignments. Реальных установок/включения ZIP/сетевых проверок/rollback/restore не выполнялось. Только статичные макеты.


## Раунд v15 · малые исправления 20a
Pages / PageTree — v1; ZIP gate read-only и default OFF. Отказ описан только на уровне приложения, с повторными проверками перед активацией, без обещания остановки body HTTP. Политика читается по CMS_UPLOAD_POLICY_FILE (absolute/private/outside web root), JSON {"module_zip_upload_enabled":true}; missing/error → disabled. На результатах rollback saved версия явно «уже использована (slot consumed)». Старый cms_v14_delivery.json исправлен теми же правилами; текущий report cms_v15_delivery.json.
