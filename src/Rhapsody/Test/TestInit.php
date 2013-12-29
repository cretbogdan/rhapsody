<?php
/*
 * This file bootstraps the test environment.
 */
// namespace Rhapsody\Test;

error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . "/../../../vendor/autoload.php";

Rhapsody::setup(array(
    'dbname' => 'rhapsody_test',
    'user' => 'root',
    'password' => '123456',
    'host' => 'localhost',
    'driver' => 'pdo_mysql'
));

Rhapsody::enableQueryLogger();
$conn = Rhapsody::getConnection();

$sql = 'CREATE TABLE IF NOT EXISTS `author` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

$conn->executeUpdate($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `author_id` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8';

$conn->executeUpdate($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `tag` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8';

$conn->executeUpdate($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `book_tag` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `book_id` int(11) NOT NULL,
 `tag_id` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `book_id` (`book_id`,`tag_id`),
 KEY `book_id_2` (`book_id`),
 KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8';

$conn->executeUpdate($sql);
