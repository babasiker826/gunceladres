FROM php:8.1-apache

# Gerekli extension'ları kur
RUN docker-php-ext-install curl json

# Apache rewrite modülü aktif et
RUN a2enmod rewrite

# Uygulama dosyalarını kopyala
COPY . /var/www/html/

# Port ayarı
EXPOSE 80

# Apache başlat
CMD ["apache2-foreground"]
