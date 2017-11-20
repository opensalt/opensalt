FROM nginx:1.13.5-alpine

RUN rm -rf /etc/nginx
COPY etc/nginx/ /etc/nginx/
COPY run.sh /
VOLUME /etc/nginx

CMD ["/run.sh"]
