FROM conetix/wordpress-with-wp-cli

RUN apt-get update && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql

ENTRYPOINT echo -e "`/sbin/ip route|awk '/default/ { print $3 }'`\tdocker.host.internal" | sudo tee -a /etc/hosts > /dev/null
