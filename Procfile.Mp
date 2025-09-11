web: heroku-php-nginx -C nginx_app.conf public/MerchantPortal
release: composer run compile-zed
