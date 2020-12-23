-- MariaDB dump 10.18  Distrib 10.5.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: pointofsale
-- ------------------------------------------------------
-- Server version	10.5.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suburb` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attachment`
--

DROP TABLE IF EXISTS `attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uploader_user_id` int(11) NOT NULL,
  `file_type_id` int(11) DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint(20) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `status` enum('ACTIVE','DELETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploader_user_id` (`uploader_user_id`),
  CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`uploader_user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_account`
--

DROP TABLE IF EXISTS `bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_a_payment_method` enum('NO','YES') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_movement`
--

DROP TABLE IF EXISTS `bank_movement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_movement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receive_by_user_id` int(11) NOT NULL,
  `client_user_id` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `type` enum('expense','income') COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_attachment_id` int(11) NOT NULL,
  `invoice_attachment_id` int(11) NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `receive_by_user_id` (`receive_by_user_id`),
  KEY `client_user_id` (`client_user_id`),
  KEY `receipt_attachment_id` (`receipt_attachment_id`),
  KEY `invoice_attachment_id` (`invoice_attachment_id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `sell_id` (`order_id`),
  CONSTRAINT `bank_movement_ibfk_1` FOREIGN KEY (`client_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `bank_movement_ibfk_2` FOREIGN KEY (`client_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `bank_movement_ibfk_3` FOREIGN KEY (`invoice_attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `bank_movement_ibfk_4` FOREIGN KEY (`receipt_attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `bank_movement_ibfk_5` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `bank_movement_ibfk_6` FOREIGN KEY (`bank_account_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `brand`
--

DROP TABLE IF EXISTS `brand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `brand_ibfk_1` (`image_id`),
  CONSTRAINT `brand_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cart_item`
--

DROP TABLE IF EXISTS `cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `type` enum('IN_CART','BUY_LATER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN_CART',
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`item_id`),
  KEY `item_id` (`item_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `cart_item_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `modified_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`),
  CONSTRAINT `category_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=520 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currency` (
  `id` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_type`
--

DROP TABLE IF EXISTS `file_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_type` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `content_type` varchar(200) NOT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `is_image` enum('NO','YES') NOT NULL,
  `image_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`),
  CONSTRAINT `file_type_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uploader_user_id` int(11) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploader_user_id` (`uploader_user_id`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`uploader_user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `on_sale` enum('NO','YES') COLLATE utf8mb4_unicode_ci NOT NULL,
  `availability_type` enum('ON_STOCK','BY_ORDER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ON_STOCK',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_price` decimal(15,5) NOT NULL DEFAULT 0.00000,
  `clave_sat` int(11) DEFAULT NULL,
  `unidad_medida_sat` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `provider_id` (`provider_id`),
  KEY `item_ibfk_4` (`brand_id`),
  KEY `category_id` (`category_id`),
  KEY `image_id` (`image_id`),
  CONSTRAINT `item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `item_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `provider` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `item_ibfk_4` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `item_ibfk_5` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `item_ibfk_6` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7515 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_attribute`
--

DROP TABLE IF EXISTS `item_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `attribute_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribute_value` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `item_attribute_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_extra`
--

DROP TABLE IF EXISTS `item_extra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_extra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `parent_item_id` int(11) NOT NULL,
  `item_option_id` int(11) DEFAULT NULL,
  `included_qty` int(11) NOT NULL,
  `max_extra_qty` int(11) NOT NULL,
  `included_price` decimal(10,0) NOT NULL,
  `extra_price` decimal(10,2) NOT NULL,
  `charge_type` enum('OPTIONAL','INCLUDED','EXTRA_CHARGE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `secondary_item_id` (`parent_item_id`),
  KEY `item_option_id` (`item_option_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `item_extra_ibfk_4` FOREIGN KEY (`item_option_id`) REFERENCES `item_option` (`id`),
  CONSTRAINT `item_extra_ibfk_5` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  CONSTRAINT `item_extra_ibfk_6` FOREIGN KEY (`parent_item_id`) REFERENCES `item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_option`
--

DROP TABLE IF EXISTS `item_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `option_name` varchar(35) COLLATE utf8mb4_unicode_ci NOT NULL,
  `included_options_qty` int(11) DEFAULT NULL,
  `max_options_qty` int(11) DEFAULT NULL,
  `included_extra_qty` int(11) NOT NULL,
  `max_extra_qty` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `item_option_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Es para llevar el nombre de control de extra ej: color, aderezo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `keyboard_shortcut`
--

DROP TABLE IF EXISTS `keyboard_shortcut`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keyboard_shortcut` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_combination` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_user_id` int(11) DEFAULT NULL,
  `cashier_user_id` int(11) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `shipping_address_id` int(11) DEFAULT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.08,
  `price_type_id` int(11) NOT NULL,
  `currency_id` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `attended_status` enum('PENDING','ATTENDED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_timetamp` timestamp NULL DEFAULT NULL,
  `client_name` int(11) NOT NULL,
  `service_type` enum('TOGO','IN_PLACE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_status` enum('PENDING','SENT','DELIVERY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `total` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `address` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suburb` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`client_user_id`),
  KEY `store_id` (`store_id`),
  KEY `currency_id` (`currency_id`),
  KEY `order_ibfk_4` (`price_type_id`),
  KEY `shipping_address_id` (`shipping_address_id`),
  KEY `cashier_user_id` (`cashier_user_id`),
  CONSTRAINT `order_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`),
  CONSTRAINT `order_ibfk_4` FOREIGN KEY (`price_type_id`) REFERENCES `price_type` (`id`),
  CONSTRAINT `order_ibfk_6` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`),
  CONSTRAINT `order_ibfk_7` FOREIGN KEY (`shipping_address_id`) REFERENCES `address` (`id`),
  CONSTRAINT `order_ibfk_8` FOREIGN KEY (`cashier_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `order_ibfk_9` FOREIGN KEY (`client_user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` enum('ACTIVE','DELETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `commanda_status` enum('NOT_DISPLAYED','PENDING','DISMISSED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_DISPLAYED',
  `item_id` int(11) NOT NULL,
  `item_option_id` int(11) DEFAULT NULL COMMENT 'si el item es una \r\nopcion de otro item, poner aqui el id el item_option, si es el item principal poner null',
  `item_extra_id` int(11) NOT NULL,
  `is_item_extra` enum('NO','YES') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_id` int(11) NOT NULL,
  `qty` decimal(12,3) NOT NULL,
  `original_unitary_price` decimal(10,3) NOT NULL,
  `unitary_price` decimal(10,3) NOT NULL,
  `subtotal` decimal(10,3) NOT NULL,
  `tax` decimal(10,3) NOT NULL,
  `total` decimal(10,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `item_id` (`item_id`),
  KEY `price_id` (`price_id`),
  KEY `item_option_id` (`item_option_id`),
  KEY `item_extra_id` (`item_extra_id`),
  CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `order_item_ibfk_3` FOREIGN KEY (`price_id`) REFERENCES `price` (`id`),
  CONSTRAINT `order_item_ibfk_4` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  CONSTRAINT `order_item_ibfk_5` FOREIGN KEY (`item_option_id`) REFERENCES `item_option` (`id`),
  CONSTRAINT `order_item_ibfk_6` FOREIGN KEY (`item_extra_id`) REFERENCES `item_extra` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `type` enum('INCOME','EXPENSE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `received_by_user_id` int(11) DEFAULT NULL,
  `paid_by_user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `user_id` (`received_by_user_id`),
  KEY `order_id` (`order_id`),
  KEY `paid_by_user_id` (`paid_by_user_id`),
  CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`received_by_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `payment_ibfk_4` FOREIGN KEY (`paid_by_user_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paypal_access_token`
--

DROP TABLE IF EXISTS `paypal_access_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paypal_access_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `access_token` varchar(255) NOT NULL,
  `raw_response` text DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp(),
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paypal_order`
--

DROP TABLE IF EXISTS `paypal_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paypal_order` (
  `id` varchar(100) NOT NULL,
  `buyer_user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(30) NOT NULL,
  `create_response` text NOT NULL,
  `log` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `default_product_image_id` int(11) DEFAULT NULL,
  `logo_image_id` int(11) DEFAULT NULL,
  `login_image_id` int(11) DEFAULT NULL,
  `default_user_logo_image_id` int(11) DEFAULT NULL,
  `default_file_logo_image_id` int(11) DEFAULT NULL,
  `background_image_id` int(11) DEFAULT NULL,
  `login_background_image_id` int(11) DEFAULT NULL,
  `chat_upload_image_id` int(11) DEFAULT NULL,
  `chat_upload_attachment_image_id` int(11) DEFAULT NULL,
  `header_color` varchar(30) DEFAULT '#99C150',
  `menu_subsection_color` varchar(30) DEFAULT '#DDDDDD',
  `menu_background_color` varchar(60) NOT NULL DEFAULT '#99C150',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `logo_image_id` (`logo_image_id`),
  KEY `default_employee_logo_image_id` (`default_user_logo_image_id`),
  KEY `login_image_id` (`login_image_id`),
  KEY `background_image_id` (`background_image_id`),
  KEY `default_file_logo_image_id` (`default_file_logo_image_id`),
  KEY `chat_upload_image_id` (`chat_upload_image_id`),
  KEY `chat_upload_attachment_image_id` (`chat_upload_attachment_image_id`),
  KEY `login_background_image_id` (`login_background_image_id`),
  KEY `default_product_image_id` (`default_product_image_id`),
  CONSTRAINT `preferences_ibfk_1` FOREIGN KEY (`background_image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_2` FOREIGN KEY (`chat_upload_attachment_image_id`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_3` FOREIGN KEY (`default_file_logo_image_id`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_4` FOREIGN KEY (`default_user_logo_image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_5` FOREIGN KEY (`login_background_image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_6` FOREIGN KEY (`logo_image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `preferences_ibfk_7` FOREIGN KEY (`default_product_image_id`) REFERENCES `image` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preparation_area`
--

DROP TABLE IF EXISTS `preparation_area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preparation_area` (
  `id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `price`
--

DROP TABLE IF EXISTS `price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `currency_id` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `item_id` int(11) NOT NULL,
  `price_type_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `price_type_id` (`price_type_id`),
  KEY `item_id` (`item_id`),
  KEY `currency_id` (`currency_id`),
  CONSTRAINT `price_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`),
  CONSTRAINT `price_ibfk_2` FOREIGN KEY (`price_type_id`) REFERENCES `price_type` (`id`),
  CONSTRAINT `price_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  CONSTRAINT `price_ibfk_4` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=481819 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `price_type`
--

DROP TABLE IF EXISTS `price_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provider`
--

DROP TABLE IF EXISTS `provider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `updated_by_user_id` (`updated_by_user_id`),
  CONSTRAINT `provider_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `provider_ibfk_2` FOREIGN KEY (`updated_by_user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`),
  CONSTRAINT `stock_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `store`
--

DROP TABLE IF EXISTS `store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_user_id` int(11) DEFAULT NULL,
  `exchange_rate` decimal(10,2) NOT NULL,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.08,
  `city` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`),
  KEY `client_id` (`client_user_id`),
  CONSTRAINT `store_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `store_bank_account`
--

DROP TABLE IF EXISTS `store_bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_bank_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `store_bank_account_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_account` (`id`),
  CONSTRAINT `store_bank_account_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `store_currency_rate`
--

DROP TABLE IF EXISTS `store_currency_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_currency_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `first_currency_id` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_currency_id` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(15,5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `first_currency_id` (`first_currency_id`),
  KEY `second_currency_id` (`second_currency_id`),
  CONSTRAINT `store_currency_rate_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`),
  CONSTRAINT `store_currency_rate_ibfk_2` FOREIGN KEY (`first_currency_id`) REFERENCES `currency` (`id`),
  CONSTRAINT `store_currency_rate_ibfk_3` FOREIGN KEY (`second_currency_id`) REFERENCES `currency` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `default_shipping_address_id` int(11) DEFAULT NULL,
  `price_type_id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `username` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_id` int(11) DEFAULT NULL,
  `business_name` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `image_id` (`image_id`),
  KEY `price_type_id` (`price_type_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`),
  CONSTRAINT `user_ibfk_3` FOREIGN KEY (`price_type_id`) REFERENCES `price_type` (`id`),
  CONSTRAINT `user_ibfk_4` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_permission`
--

DROP TABLE IF EXISTS `user_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission` enum('ROOT','ADMIN','PREPARATION','POS','ORDER_DISPLAY','WAITER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_user` (`user_id`,`permission`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-12-23 14:04:42
