-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: xanarchy_bd
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `xanarchy_bd`
--

/*!40000 DROP DATABASE IF EXISTS `xanarchy_bd`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `xanarchy_bd` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `xanarchy_bd`;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `country` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_addresses_user` (`user_id`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (2,3,'Diego','Casillas','Mexico','Zapatista #141','Aguascalientes','Aguascalientes','20126','4491648686','palmera','2025-07-14 16:50:41'),(3,2,'Luis','Alejandro','Mexico','Zapatista #141','Aguascalientes','Aguascalientes','20126','4491648686','frfrf','2025-07-21 16:50:09'),(5,19,'luisito','Garduno','Mexico','Zapatistas #141','Aguascalientes','Aguascalientes','20126','4491648686','','2026-05-27 23:21:53');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_interactions`
--

DROP TABLE IF EXISTS `crm_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_interactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `crm_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_interactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_interactions`
--

LOCK TABLES `crm_interactions` WRITE;
/*!40000 ALTER TABLE `crm_interactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_interactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (5,3,6,1,399.00),(6,3,9,1,200.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (3,3,719.00,'shipped','2025-07-17 21:08:06'),(4,3,479.99,'shipped','2026-05-26 03:31:10'),(5,3,550.00,'shipped','2026-05-26 03:31:10'),(6,23,50.00,'paid','2026-09-03 03:19:07'),(7,23,75.00,'paid','2026-09-03 03:19:07'),(8,24,100.00,'paid','2026-09-03 03:19:07'),(9,24,120.00,'paid','2026-09-03 03:19:07'),(10,24,45.00,'paid','2026-09-03 03:19:07'),(11,24,80.00,'paid','2026-09-03 03:19:07');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Playera Gótica Negra','Playera con estampado gótico, 100% algodón. Disponible en varias tallas.',329.00,0.00,'Imagenes/Playera_Gotica_Negra.png',10,'2025-05-24 01:55:35'),(2,'Playera Gervonta','Playera de gervonta estampada',599.00,0.00,'Imagenes/Productos/prod_6834734d3d2d9.png',10,'2025-05-26 13:57:33'),(3,'Playera estampada con flores','Playera negra con delicado estampado de rosas, que aporta un toque de elegancia y rebeldía. Ideal para looks casuales con personalidad.',249.00,0.00,'Imagenes/Playera estampada con flores.png',10,'2025-05-26 14:00:40'),(4,'Playera estampada Gatos Bad Cattitude para Mujer','Playera cómoda y casual para mujer con estampado cool y original de un gato con actitud. Perfecta para un look juvenil y desenfadado.',195.00,0.00,'Imagenes/Playera Estampada Gatos.png',15,'2025-05-26 14:00:40'),(5,'Playera estampada con imagen gótica de Jesús y cruces','Impactante playera negra con diseño gótico que representa a Jesús rodeado de cruces. Ideal para quienes buscan un estilo alternativo y simbólico.',299.00,0.00,'Imagenes/Playera estampada jesus.png',8,'2025-05-26 14:00:40'),(6,'Playera estampada Travis Scott','Playera negra con estampado exclusivo de Travis Scott, ideal para fans del artista y amantes de la cultura urbana. Confeccionada en algodón suave para máxima comodidad.',399.00,0.00,'Imagenes/PLAYERA ESTAMPADA TRAVIS SCOTT.png',20,'2025-05-26 14:00:40'),(7,'Playera Return of Misfits','Kingmonster Playera Return of Misfits, estampado al frente de alta durabilidad y ligero al tacto.',298.00,0.00,'Imagenes/Playera Return of Misfits.png',12,'2025-05-26 14:00:40'),(8,'Playera con estampado de astronauta','Playera con ilustración de astronauta que inspira aventura y exploración. Diseño moderno y cómodo para destacar en cualquier lugar.',200.00,0.00,'Imagenes/playera con estampado de astronauta.png',18,'2025-05-26 14:00:40'),(9,'Playera con estampado antiguo','Playera con diseño vintage único que combina estilo clásico y comodidad. Perfecta para quienes aman la moda retro y desean destacar con un look atemporal.',200.00,0.00,'Imagenes/playera con estampado antiguo.png',25,'2025-05-26 14:00:40'),(10,'Playera de manga corta para hombre','Camisetas para hombre, playera de manga corta de poliéster suave, diseño clásico de cuello redondo y mangas cortas, ideal para el día a día.',205.00,0.00,'Imagenes/Playera de manga corta para hombre.png',30,'2025-05-26 14:00:40'),(11,'Playera 2032 Bad Bunny','Playera 2032 de algodon negra inspirada en Bad Bunny',399.00,0.00,'Imagenes/prod_687960a0916523.70562781.jpg',8,'2025-07-17 20:44:16'),(19,'Prueba estructuras complejas ','hola 123',10.00,0.00,'Imagenes/prod_6a1a4dfeb064a7.89386709.jpg',5,'2026-05-30 02:39:58');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `crm_stage` enum('Prospecto','Activo','Frecuente','Inactivo') NOT NULL DEFAULT 'Prospecto',
  `crm_stage_manual` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Menton Juarez','prueba@gmail.com','$2y$10$akREQ2L0il1/XcSUbr5b3ev/o.1uVWF3ykuwDKfpgQz4JVVlncpOG','customer','2025-05-24 00:54:40','Inactivo',0),(3,'Juan Perez Actualizado','juan.perez.nuevo@gmail.com','$2y$10$fklaf/9/iZ45MCgZQ4WrL./l.PxIS9U1sxIdvR0r8zM0ie4bFVEaO','customer','2025-07-14 16:49:48','Inactivo',0),(8,'Luis Alejandro','admin@estampa.com','$2y$10$Nu35w4pteLfc7BDCIkDPkecjw8wsH8Y2GMfIewUbXLT7zzW6WOxwq','admin','2025-07-17 02:59:28','Prospecto',0),(9,'Diego Casillas','admin2@estampa.com','$2y$10$dIpRmo79IQZgtAK/h97B0etxrL.GSLlZQ9jV5XrXqVRp9x8pv/6oS','admin','2025-07-17 21:28:49','Prospecto',0),(12,'Alex','aadmin@estampa.com','$2y$10$mpk8qDYOk4xAZ680N57zf.Ds0PLtjkEoP5TGNrR4CtGdTZmold7RW','admin','2025-11-03 17:27:55','Prospecto',0),(13,'Admin_Alex','admin@estampatla.com','$2y$10$B00Z5A1.U6uU3jZ.2L1E1.m.p1F.s.w.z.1.x.G.4.k.Z.y.C.D.W','admin','2026-05-22 18:24:36','Prospecto',0),(15,'Harold Admin','harold@estampatla.com','$2y$10$HashedPasswordAdmin1','admin','2026-05-26 03:28:28','Prospecto',0),(16,'Luis Alejandro Dev','luis@estampatla.com','$2y$10$HashedPasswordAdmin2','admin','2026-05-26 03:28:28','Prospecto',0),(17,'Juan Perez','juan.perez@gmail.com','$2y$10$HashedPasswordCustomer1','customer','2026-05-26 03:28:28','Inactivo',0),(18,'Maria Lopez','maria.l@yahoo.com','$2y$10$HashedPasswordCustomer2','customer','2026-05-26 03:28:28','Inactivo',0),(19,'Alex','luisitogarduno1.ext@gmail.com','$2y$10$ADg2KzD/DHVCIADHHtKY8.QQDf7XC/R5wZl2lq39.jWXNebllVAvq','customer','2026-05-27 23:21:38','Inactivo',0),(21,'Santa Leonarda','pedro@test.com','123','customer','2026-09-03 03:19:07','Prospecto',0),(22,'Ana Test','ana@test.com','123','customer','2026-09-03 03:19:07','Prospecto',0),(23,'Juan Activo','juan@test.com','123','customer','2026-09-03 03:19:07','Activo',0),(24,'Maria Frecuente','maria@test.com','123','customer','2026-09-03 03:19:07','Frecuente',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vista_detalles_ordenes`
--

DROP TABLE IF EXISTS `vista_detalles_ordenes`;
/*!50001 DROP VIEW IF EXISTS `vista_detalles_ordenes`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_detalles_ordenes` AS SELECT
 1 AS `order_id`,
  1 AS `client_name`,
  1 AS `total`,
  1 AS `status`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Current Database: `xanarchy_bd`
--

USE `xanarchy_bd`;

--
-- Final view structure for view `vista_detalles_ordenes`
--

/*!50001 DROP VIEW IF EXISTS `vista_detalles_ordenes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_detalles_ordenes` AS select `o`.`id` AS `order_id`,`u`.`name` AS `client_name`,`o`.`total` AS `total`,`o`.`status` AS `status`,`o`.`created_at` AS `created_at` from (`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-02 21:57:29
