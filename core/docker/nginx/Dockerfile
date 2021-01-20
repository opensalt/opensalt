FROM nginx:1.18-alpine

RUN rm -rf /etc/nginx
COPY etc/nginx/ /etc/nginx/
COPY run.sh /
VOLUME /etc/nginx

CMD ["/run.sh"]
