FROM alpine:3.20 AS protoc

ARG PROTOC_VERSION=32.1

RUN apk add --no-cache curl unzip && \
    curl -LO "https://github.com/protocolbuffers/protobuf/releases/download/v${PROTOC_VERSION}/protoc-${PROTOC_VERSION}-linux-x86_64.zip" && \
    unzip "protoc-${PROTOC_VERSION}-linux-x86_64.zip" -d /usr/local && \
    rm "protoc-${PROTOC_VERSION}-linux-x86_64.zip"

FROM ghcr.io/phpyh/php:8.4

RUN apk add --no-cache bash

COPY --from=protoc /usr/local/bin/protoc /usr/local/bin/protoc
COPY --from=protoc /usr/local/include /usr/local/include
