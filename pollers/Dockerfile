FROM php:5.6-cli
MAINTAINER SportArchive, Inc.

RUN echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/timezone.ini
RUN apt-get update \
    && apt-get install -y zlib1g-dev \
    && docker-php-ext-install zip

COPY . /usr/src/cloudprocessingengine
WORKDIR /usr/src/cloudprocessingengine
RUN apt-get update \
    && apt-get install -y git \
    && make \
    && apt-get purge -y git \
    && apt-get autoremove -y

ENTRYPOINT ["/usr/src/cloudprocessingengine/bootstrap.sh"]
