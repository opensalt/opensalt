FROM mhart/alpine-node

COPY package.json /tmp/package.json

RUN apk --no-cache add \
      python \
      python-dev \
      py-pip \
      build-base && \
    mkdir /build && \
    cp /tmp/package.json /build/ && rm -f /tmp/package.json && \
    cd /build && \
    npm install && \
    apk del \
      python \
      python-dev \
      py-pip \
      build-base && \
    mkdir /build/app

WORKDIR /build/app

ENTRYPOINT ["/build/node_modules/gulp/bin/gulp.js"]
CMD []
