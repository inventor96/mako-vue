# ------- Base stage -------
FROM node:21.6 AS base
WORKDIR /app
COPY package.json package-lock.json* ./

# ------- Dev stage -------
FROM base AS dev

# Allow configurable UID/GID
ARG UID=1000
ARG GID=1000
RUN if ! getent group ${GID} >/dev/null; then \
      groupadd -g ${GID} devuser; \
    fi
RUN if ! id -u ${UID} >/dev/null 2>&1; then \
      useradd -u ${UID} -g ${GID} -m devuser; \
    fi

# Install dependencies
COPY docker/frontend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT [ "entrypoint.sh" ]

# Fire in the hole!
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
