.PHONY: up down test lint e2e release composer-install frontend-build

PHP_VERSION ?= 8.4
MYSQL_IMAGE_TAG ?= 8.0
MYSQL_SERVER_VERSION ?= $(MYSQL_IMAGE_TAG)
export PHP_VERSION MYSQL_IMAGE_TAG MYSQL_SERVER_VERSION

up:
	docker compose build app
	docker compose up -d mysql mailpit app
	@echo "App: http://localhost:$${APP_PORT:-8080}  Mailpit: http://localhost:8025"

up-mysql84:
	docker compose -f docker-compose.yml -f docker-compose.mysql84.yml build app
	docker compose -f docker-compose.yml -f docker-compose.mysql84.yml up -d mysql mailpit app
	@echo "MySQL 8.4 stack: http://localhost:$${APP_PORT:-8080}"

down:
	docker compose down -v

composer-install:
	docker compose run --rm --no-deps --entrypoint composer cli install --no-interaction --ignore-platform-reqs

frontend-build:
	cd admin-ui && npm ci && npm run build

lint: composer-install
	docker compose run --rm --entrypoint bash cli -c "composer phpstan && composer lint:php"
	cd admin-ui && npm ci && npm run lint && npm run typecheck

test: composer-install frontend-build
	docker compose run --rm --entrypoint bash cli -c "\
		composer test && \
		DATABASE_URL=mysql://karkas:karkas_secret@mysql:3306/karkas_test?serverVersion=$(MYSQL_IMAGE_TAG)\\&charset=utf8mb4 composer test:integration"
	$(MAKE) e2e

e2e:
	cd admin-ui && npm ci
	cd e2e && npm ci && npx playwright install chromium --with-deps
	cd e2e && BASE_URL=http://localhost:$${APP_PORT:-8080} npm test
	docker compose run --rm --no-deps -e STAGE0_HTTP_PROBE_BASE_URL=http://host.docker.internal:$${APP_PORT:-8080} cli vendor/bin/phpunit --filter ApacheWebSapiProbeTest

release:
	bash scripts/build-release.sh

release-verify:
	bash scripts/build-release.sh --verify-only
