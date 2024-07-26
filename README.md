# Symfony Recommendation Engine

## Overview

This project is a Symfony-based recommendation engine designed to improve customer experience by analyzing user behavior, purchase history, and product attributes to suggest relevant products. It includes functionalities for user management, product management, purchase history, and personalized recommendations.

## Features

- User Management

  - Register new users
  - User authentication
  - List, view, update, and delete users

- Product Management

  - Add new products
  - List, view, update, and delete products

- Purchase History

  - Track user purchases
  - List, view, update, and delete purchase records

- Recommendation Engine
  - Personalized product recommendations based on user behavior and purchase history

## Technologies Used

- PHP 8.x
- Symfony 7.x
- MySQL
- Doctrine ORM
- PHPUnit for testing
- OpenAPI (Swagger) for API documentation
- Monolog for logging

## Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/katkamravikanth/recommendation-engine.git
   cd recommendation-engine
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Create and configure environment variables:**

   Copy `.env.dist` file to `.env` and configure your database and other environment variables.

   ```bash
   cp .env.dist .env
   ```

   Update the `.env` file with your database configuration and other necessary settings.

4. **Create the database:**

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Load fixtures:**

   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. **Run the Symfony server:**

   ```bash
   symfony server:start
   ```

7. **Access the application:**

   Open your browser and navigate to `http://localhost:8000`.

## API Documentation

API documentation is available at `http://localhost:8000/api/docs`. This documentation is generated using OpenAPI (Swagger) and provides detailed information about each endpoint, including request and response formats.

## Running Tests

To run the tests, use the following command:

```bash
php bin/phpunit
```

This will execute all tests in the tests directory and provide a summary of the results.

### Project Structure

├── config/ # Configuration files
├── migrations/ # Database migration files
├── public/ # Publicly accessible files
├── src/ # Application source code
│ ├── Controller/ # Controllers
│ ├── Entity/ # Doctrine entities
│ ├── EventListener/ # Event listeners
│ ├── Repository/ # Repositories
│ ├── Service/ # Services
│ └── Tests/ # Test cases
├── templates/ # Twig templates
├── tests/ # Test files
└── var/ # Application data

## Acknowledgements

    Symfony Documentation
    OpenAPI (Swagger) Documentation
    PHPUnit Documentation
