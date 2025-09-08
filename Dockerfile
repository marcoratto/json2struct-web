# Usa l'immagine ufficiale PHP con Apache
FROM php:8.2-apache

# Copia i file del sito nella cartella di Apache
COPY src/ /var/www/html/

# Abilita mod_rewrite (utile per molti framework tipo Laravel/WordPress)
RUN a2enmod rewrite

# Espone la porta 80
EXPOSE 80
