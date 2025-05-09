FROM percona/percona-server:8.4

USER root
COPY docker-healthcheck /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-healthcheck
HEALTHCHECK --interval=30s --timeout=3s --retries=3 --start-period=60s CMD ["docker-healthcheck"]

COPY *.cnf /etc/my.cnf.d/

USER mysql
