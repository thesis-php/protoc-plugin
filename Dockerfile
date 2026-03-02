FROM alpine:3.20

ARG PROTOC_VERSION=33.5
ARG PROTOC_PLUGIN_VERSION

RUN apk add --no-cache curl unzip && \
    curl -LO "https://github.com/protocolbuffers/protobuf/releases/download/v${PROTOC_VERSION}/protoc-${PROTOC_VERSION}-linux-x86_64.zip" && \
    unzip "protoc-${PROTOC_VERSION}-linux-x86_64.zip" -d /usr/local && \
    rm "protoc-${PROTOC_VERSION}-linux-x86_64.zip"

RUN curl -L "https://github.com/thesis-php/protoc-plugin/releases/download/${PROTOC_PLUGIN_VERSION}/protoc-gen-php" \
    -o /usr/local/bin/protoc-gen-php \
    && chmod +x /usr/local/bin/protoc-gen-php

ENTRYPOINT ["protoc", "--plugin=protoc-gen-php-plugin=/usr/local/bin/protoc-gen-php"]
