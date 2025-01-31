# Use official PHP image
FROM php:8.0-cli

# Install dependencies (if needed)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www/html

# Copy the application files into the container
COPY . .

# Expose the port
EXPOSE 8080

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080"]
