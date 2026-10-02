
include make-compose.mk

PORT ?= 8000

console:
	php artisan tinker

deploy:
	git push heroku main

setup: env-prepare install key db-prepare ide-helper generate-types
	npm run build

install-app:
	composer install

install-frontend:
	npm ci

install: install-app install-frontend

start:
	heroku local -f Procfile.dev

start-app:
	php artisan serve --host 0.0.0.0 --port ${PORT}

start-frontend:
	npm run dev

db-prepare:
	php artisan migrate:fresh --force --seed

lint: lint-js lint-ts lint-frontend-rules types-check lint-php

lint-fix:
	composer exec phpcbf -v
	npx prettier --write resources/**/*.blade.php

test:
	php artisan test

test-solutions:
	composer exec phpunit -- --testsuite "Exercises"

test-sandbox:
	composer exec phpunit -- --testsuite "Sandbox"

test-coverage:
	XDEBUG_MODE=coverage php artisan test --coverage-clover build/logs/clover.xml

analyse:
	# composer exec phpstan analyse -v -- --memory-limit=512M
	@echo 'fixme'

check: test lint analyse

config-clear:
	php artisan config:clear

cache-clear:
	php artisan config:clear
	php artisan cache:clear
	php artisan view:clear

storage-link:
	php artisan storage:link

db-migrate:
	php artisan migrate --force

db-migrate-seed:
	php artisan migrate --force --seed

db-seed:
	php artisan db:seed --force

env-prepare:
	cp -n .env.example .env || true

key:
	php artisan key:generate

ide-helper:
	php artisan ide-helper:eloquent
	php artisan ide-helper:gen
	php artisan ide-helper:meta
	php artisan ide-helper:mod -n

lint-js:
	npm run lint-js

# Правила hybrid-периода (docs/agents/frontend.md), только для нового TS-кода:
# URL приходят с бэкенда; data-method без @rails/ujs уходит GET-ом; window/document на верхнем уровне ломают SSR.
lint-frontend-rules:
	@! grep -rnE --include='*.ts' --include='*.tsx' "href=(\"/|\{['\"\`]/)" resources/js \
		|| (echo 'URL склеен в JS — передай его пропом с бэкенда'; exit 1)
	@! grep -rnE --include='*.tsx' 'data-(method|confirm)=' resources/js \
		|| (echo 'data-method/data-confirm не работают на Inertia-странице — router.* и modals.openConfirmModal()'; exit 1)
	@! grep -rnE --include='*.ts' --include='*.tsx' '^[^ /*].*\b(window|document|localStorage)\.' resources/js \
		|| (echo 'window/document на верхнем уровне модуля — перенеси в useEffect или обработчик'; exit 1)

lint-ts:
	npm run types

generate-types:
	php artisan typescript:transform

types-check: generate-types
	git diff --exit-code -- resources/js/types/generated.d.ts

lint-php:
	composer exec phpcs -v

lint-js-fix:
	npm run lint-js-fix

setup-git-hooks:
	npx simple-git-hooks

.PHONY: test

pre-push-hook: lint analyse

docker-build-render:
	DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 docker build . -t hexlet-sicp:cached

stage-init: key db-seed storage-link

stage-update: cache-clear
