FROM percona:5.7

USER root
COPY docker-healthcheck /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-healthcheck
HEALTHCHECK --interval=30s --timeout=3s --retries=3 --start-period=60s CMD ["docker-healthcheck"]

COPY max_allowed_packet.cnf /etc/mysql/conf.d/max_allowed_packet.cnf
COPY charset.cnf /etc/mysql/conf.d/charset.cnf

USER mysql
