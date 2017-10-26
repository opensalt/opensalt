FROM node:8-alpine

RUN mkdir /build
COPY node/* /build/

RUN \
    cd /build && \
    yarn install && \
    mkdir /build/app

WORKDIR /build/app

ENTRYPOINT ["/build/node_modules/.bin/encore"]
CMD []
