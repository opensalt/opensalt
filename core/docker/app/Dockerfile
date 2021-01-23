FROM tianon/true:latest

ARG BUILD_DATE
ARG VERSION
ARG VCS_URL
ARG VCS_REF

LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="OpenSALT Application Code" \
      org.label-schema.description="Contains the OpenSALT Application Code, needs to be used in conjunction with containers for PHPFPM and a web server" \
      org.label-schema.url="https://github.com/opensalt/opensalt" \
      org.label-schema.vcs-ref=$VCS_REF \
      org.label-schema.vcs-url=$VCS_URL \
      org.label-schema.version=$VERSION \
      org.label-schema.schema-version="1.0"

VOLUME /var/www/html
ADD app.tar /var/www/html
WORKDIR /var/www/html
