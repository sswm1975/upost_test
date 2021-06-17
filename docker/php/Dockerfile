FROM php:7.4-apache
MAINTAINER p-Clerick
RUN apt-get update && apt-get install -y --fix-missing \
    apt-utils \
    gnupg

RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN curl -sS --insecure https://www.dotdeb.org/dotdeb.gpg | apt-key add -

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    libxml2-dev \
    mc \
    git \
    unzip \
    wget \
    libpng-dev

RUN docker-php-ext-install  gd soap zip mysqli pdo_mysql bcmath exif sockets

ADD ./php.ini /usr/local/etc/php/php.ini

RUN a2enmod rewrite
RUN service apache2 restart

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet

RUN groupadd --gid 1001 dev \
  && useradd --uid 1000 --gid dev --shell /bin/bash --create-home dev
RUN usermod -aG www-data dev
RUN echo "export TERM=xterm-256color" > /home/dev/.profile
RUN chown -R dev:www-data /home/dev
RUN chmod -R 775 /home/dev

USER dev

