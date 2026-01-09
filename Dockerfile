FROM php:8.2-apache
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*
# Note: ImageMagick/Ghostscript installation removed - previews are generated via external API by default
RUN a2enmod rewrite
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
ENV PDF_API_KEY="nassermaxion21@gmail.com_P8hvIPbZiJdLG2Q7hm0PdfLgcun1zAXtpQMOAKF2bcqVq2TVunmvCLCRRo3tKdKi"
