# ------- Base stage -------
FROM node:24 AS base
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# ------- Dev stage -------
FROM base AS dev

# Install dependencies
COPY docker/frontend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT [ "entrypoint.sh" ]

# Fire in the hole!
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
