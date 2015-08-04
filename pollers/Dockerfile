FROM php:5.6-cli
MAINTAINER SportArchive, Inc.

COPY . /usr/src/cloudprocessingengine
WORKDIR /usr/src/cloudprocessingengine
RUN apt-get update \
    && apt-get install -y git \
    && make \
    && apt-get purge -y git \
    && apt-get autoremove -y

ENTRYPOINT ["/usr/src/cloudprocessingengine/bootstrap.sh"]
