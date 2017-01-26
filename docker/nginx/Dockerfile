FROM nginx:1.11.9-alpine

RUN rm -rf /etc/nginx
COPY etc/nginx/ /etc/nginx/
COPY run.sh /
VOLUME /etc/nginx

CMD ["/run.sh"]
