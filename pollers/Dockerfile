FROM php:5.6-cli
MAINTAINER SportArchive, Inc.

COPY . /usr/src/cloudprocessingengine
WORKDIR /usr/src/cloudprocessingengine

ENV AWS_REGION xyz
ENV AWS_ACCESS_KEY_ID xyz
ENV AWS_SECRET_ACCESS_KEY xyz

ENTRYPOINT ["/usr/src/cloudprocessingengine/bootstrap.sh"]
