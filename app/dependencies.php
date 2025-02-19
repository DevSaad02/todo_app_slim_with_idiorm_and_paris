<?php

declare(strict_types=1);

use Monolog\Logger;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Twig\Loader\FilesystemLoader;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\UidProcessor;
use Monolog\Formatter\LineFormatter;
use Psr\Container\ContainerInterface;
use App\Application\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
    // Add service definitions to the container
    $containerBuilder->addDefinitions([
        // Define the LoggerInterface service
        LoggerInterface::class => function (ContainerInterface $c) {
            // Get application settings
            $settings = $c->get(SettingsInterface::class);

            // Retrieve logger settings from the application settings
            $loggerSettings = $settings->get('logger');
            // Create a new Logger instance with the specified name
            $logger = new Logger($loggerSettings['name']);

            // Add a unique identifier processor to the logger
            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            // Use RotatingFileHandler for log rotation
            // This handler creates a new log file each day and keeps up to 30 log files
            $handler = new RotatingFileHandler($loggerSettings['path'], 30, $loggerSettings['level']);

            // Create a custom formatter with a readable date format
            $dateFormat = "d M, Y H:i:s";
            $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
            $formatter = new LineFormatter($output, $dateFormat);
            $handler->setFormatter($formatter);

            $logger->pushHandler($handler);

            // Return the configured logger
            return $logger;
        },
        // Define the Twig service
        Twig::class => function () {
            // Set the path to the templates directory
            $loader = new FilesystemLoader(__DIR__ . '/../src/Templates');
            // Create a new Twig instance with the specified loader and cache settings
            return new Twig($loader, ['cache' => false]);
        },
    ]);
};
