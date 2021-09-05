# airport_traffic_list
My own litt utility to make my workday easier when planning rosters.
It is not meant as a general tool but in some places quite particular 
to my airport. - But code is up for viewing for anyone interested.

The app is based on Avinors air traffic lists reported in by the airlines. 
I just need to see when aircraft depart or land during the day/week. 

This is a somewhat messy project. There are pieces being brought together 
from different tools created many years ago - so there is no coherent 
look between web pages.

The import routine takes a Xls sheet with traffic exported from Avinor. 

Database is MySql - and language is now PHP. No more ASP .net and 
Entity-framework, .NET version problems. 


The import program uses PHPOffice\PHPSpreadsheet
You will need composer and then in the base directory of the project: 
"composer require phpoffice/phpspreadsheet"
It will build the vendor subfolder

---

Database create statements

CREATE DATABASE `flights` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_icelandic_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

CREATE TABLE `FlightImports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `TargetAD` char(3) COLLATE utf8mb4_icelandic_ci NOT NULL,
  `importtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comments` varchar(255) COLLATE utf8mb4_icelandic_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idxTargetAd` (`TargetAD`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_icelandic_ci;

CREATE TABLE `Flights` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `DepAD` char(3) COLLATE utf8mb4_icelandic_ci NOT NULL,
  `ArrAD` char(3) COLLATE utf8mb4_icelandic_ci NOT NULL,
  `CallSign` varchar(15) COLLATE utf8mb4_icelandic_ci NOT NULL,
  `Aircraft` varchar(10) COLLATE utf8mb4_icelandic_ci NOT NULL,
  `STA` datetime NOT NULL,
  `STD` datetime NOT NULL,
  `ImportId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `idxSTA` (`STA`),
  KEY `idxSTD` (`STD`),
  KEY `idxDepAD` (`DepAD`),
  KEY `idxArrAD` (`ArrAD`),
  KEY `fk_imports` (`ImportId`),
  CONSTRAINT `fk_imports` FOREIGN KEY (`ImportId`) REFERENCES `FlightImports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34964 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_icelandic_ci;


CREATE ALGORITHM=UNDEFINED DEFINER=`admin`@`%` SQL SECURITY DEFINER VIEW `Movements` AS select `fl`.`Id` AS `FlightId`,`fl`.`DepAD` AS `DepAD`,`fl`.`ArrAD` AS `ArrAD`,`fl`.`CallSign` AS `Callsign`,`fl`.`STD` AS `STD`,`fl`.`STA` AS `STA`,if((`imp`.`TargetAD` = `fl`.`DepAD`),`fl`.`STD`,`fl`.`STA`) AS `Touch`,if((`imp`.`TargetAD` = `fl`.`DepAD`),'D','A') AS `Direction`,if((`imp`.`TargetAD` = `fl`.`DepAD`),cast(`fl`.`STD` as date),cast(`fl`.`STA` as date)) AS `OpDate`,`fl`.`ImportId` AS `ImportId`,`fl`.`Aircraft` AS `Aircraft` from (`Flights` `fl` join `FlightImports` `imp` on((`imp`.`id` = `fl`.`ImportId`)));






