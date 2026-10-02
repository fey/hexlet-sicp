# syntax = edrevo/dockerfile-plus

INCLUDE+ Dockerfile.dev

ENV PORT=80

COPY composer.json composer.lock ./
COPY app/Helpers/helpers.php ./app/Helpers/helpers.php

RUN composer install --prefer-dist --no-scripts --no-dev --no-autoloader

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./

RUN pnpm install --frozen-lockfile

COPY . .

RUN composer dump-autoload --no-dev --optimize

RUN pnpm run build

CMD ["bash", "-c", "make db-prepare start-app"]

EXPOSE ${PORT}
