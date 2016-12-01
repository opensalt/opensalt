FROM mhart/alpine-node

COPY package.json /tmp/package.json

RUN apk --no-cache add \
      python \
      python-dev \
      py-pip \
      build-base && \
    mkdir /app && \
    mkdir /build && \
    cp /tmp/package.json /build/ && rm -f /tmp/package.json && \
    cd /build && \
    npm install && \
    apk del \
      python \
      python-dev \
      py-pip \
      build-base

COPY gulp-wrapper.sh /build/gulp-wrapper.sh

WORKDIR /app

ENTRYPOINT ["sh", "-c", "/build/gulp-wrapper.sh"]
CMD [""]
