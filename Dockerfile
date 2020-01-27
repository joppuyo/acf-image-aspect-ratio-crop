FROM conetix/wordpress-with-wp-cli

RUN apt-get update && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql

