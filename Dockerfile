
#https://github.com/oberd/php-8.0-apache/blob/main/Dockerfile
FROM php:8.0-apache as base
RUN apt-get update && apt-get install --no-install-recommends -y \
  libzip-dev \
  libxml2-dev \
  mariadb-client \
  zip \
  unzip \
  && apt-get -y install tzdata cron \
  && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN pecl install zip pcov
RUN docker-php-ext-enable zip \
  && docker-php-ext-install pdo_mysql \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install soap \
  && docker-php-source delete
COPY cron /etc/cron.d/cron
RUN chmod 0644 /etc/cron.d/cron
RUN crontab /etc/cron.d/cron
RUN mkdir -p /var/log/cron
# Новый образ docker наследует от php: последнее официальное изображение apache,
# добавляет правильный часовой пояс, настраивает задание cron и, наконец,
# самое сложное - изменяет скрипт точки входа унаследованного образа (apache2-foreground),
# добавляя службу запуска cron непосредственно перед запуском процесса apache.
# Помните, что каждый контейнер привязан к одному запущенному процессу. В php: apache это должен
# быть сам apache (а не cron). Итак, идея состоит в том, чтобы запустить cron как службу и перед
# единственным запущенным процессом контейнера.
RUN sed -i 's/^exec /service cron start\n\nexec /' /usr/local/bin/apache2-foreground
COPY ./src /var/www/html