-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.7 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.15.0.7171
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para sesystem_prueba
CREATE DATABASE IF NOT EXISTS `sesystem_prueba` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sesystem_prueba`;

-- Volcando estructura para tabla sesystem_prueba.alumno
CREATE TABLE IF NOT EXISTS `alumno` (
  `idAlumno` int NOT NULL AUTO_INCREMENT,
  `idPersona` int NOT NULL,
  `nombrePadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombreMadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocupacionPadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocupacionMadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `docPadre` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `docMadre` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tutor` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personaRetiro` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legajo` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idAlumno`),
  KEY `idpersona_idx` (`idPersona`),
  CONSTRAINT `idpersona` FOREIGN KEY (`idPersona`) REFERENCES `persona` (`idPersona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.alumnosterciario
CREATE TABLE IF NOT EXISTS `alumnosterciario` (
  `idAlumno` int NOT NULL AUTO_INCREMENT,
  `idPersona` int NOT NULL,
  `nombrePadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombreMadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocupacionPadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocupacionMadre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vivePadre` tinyint(1) DEFAULT NULL,
  `viveMadre` tinyint(1) DEFAULT NULL,
  `estudiosPadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estudiosMadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefonoPadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefonoMadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailPadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailMadre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tituloIngreso` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carreraAnterior` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colegioProcedencia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anioIngreso` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beca` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaotorgbeca` date DEFAULT NULL,
  `observacionesbeca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legajo` int DEFAULT NULL,
  `alta` date DEFAULT NULL,
  `baja` date DEFAULT NULL,
  `nroMatri` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `egresado` int DEFAULT NULL,
  `foto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documentacion` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trabaja` int DEFAULT NULL,
  `idFamilia` int NOT NULL,
  `materiasAdeuda` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retiroBiblioteca` int DEFAULT NULL,
  `mailInstitucional` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idAlumno`),
  KEY `idPersona_idx` (`idPersona`),
  CONSTRAINT `alumTercPersona` FOREIGN KEY (`idPersona`) REFERENCES `persona` (`idPersona`)
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.asistencia
CREATE TABLE IF NOT EXISTS `asistencia` (
  `idAsistencia` int NOT NULL AUTO_INCREMENT,
  `legajo` int NOT NULL,
  `IdTipo` int NOT NULL,
  `fecha` date NOT NULL,
  `anio` int NOT NULL,
  PRIMARY KEY (`idAsistencia`),
  KEY `asistenciaalumno_idx` (`legajo`),
  KEY `asistenciatipo_idx` (`IdTipo`),
  CONSTRAINT `asistenciaalumno` FOREIGN KEY (`legajo`) REFERENCES `alumno` (`idAlumno`),
  CONSTRAINT `asistenciatipo` FOREIGN KEY (`IdTipo`) REFERENCES `tipoasistencia` (`idTipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.asistenciaterciario
CREATE TABLE IF NOT EXISTS `asistenciaterciario` (
  `idAsistenciaTerciario` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int NOT NULL,
  `idMateria` int NOT NULL,
  `mes` int NOT NULL,
  `idCicloLectivo` int NOT NULL,
  `d1` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d2` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d3` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d4` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d5` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d6` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d7` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d8` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d9` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d10` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d11` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d12` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d13` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d14` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d15` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d16` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d17` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d18` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d19` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d20` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d21` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d22` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d23` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d24` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d25` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d26` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d27` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d28` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d29` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d30` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d31` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tmp_apenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idAsistenciaTerciario`),
  KEY `alumno_idx` (`idAlumno`),
  KEY `materias_idx` (`idMateria`),
  KEY `ciclo_idx` (`idCicloLectivo`),
  CONSTRAINT `alumno` FOREIGN KEY (`idAlumno`) REFERENCES `alumnosterciario` (`idAlumno`),
  CONSTRAINT `ciclo` FOREIGN KEY (`idCicloLectivo`) REFERENCES `ciclolectivo` (`idciclolectivo`),
  CONSTRAINT `materias` FOREIGN KEY (`idMateria`) REFERENCES `materiaterciario` (`idMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=42277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.calculopromedios
CREATE TABLE IF NOT EXISTS `calculopromedios` (
  `idCalculo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idCalculo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.calificaciones
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `idCalificaciones` int NOT NULL AUTO_INCREMENT,
  `legajo` int NOT NULL,
  `idMateria` int NOT NULL,
  `anio` int NOT NULL,
  `n1pt` int DEFAULT NULL,
  `n2pt` int DEFAULT NULL,
  `n3pt` int DEFAULT NULL,
  `n4pt` int DEFAULT NULL,
  `n5pt` int DEFAULT NULL,
  `n6pt` int DEFAULT NULL,
  `n7pt` int DEFAULT NULL,
  `n8pt` int DEFAULT NULL,
  `promPt` float DEFAULT NULL,
  `n1st` int DEFAULT NULL,
  `n2st` int DEFAULT NULL,
  `n3st` int DEFAULT NULL,
  `n4st` int DEFAULT NULL,
  `n5st` int DEFAULT NULL,
  `n6st` int DEFAULT NULL,
  `n7st` int DEFAULT NULL,
  `n8st` int DEFAULT NULL,
  `promSt` float DEFAULT NULL,
  `n1tt` int DEFAULT NULL,
  `n2tt` int DEFAULT NULL,
  `n3tt` int DEFAULT NULL,
  `n4tt` int DEFAULT NULL,
  `n5tt` int DEFAULT NULL,
  `n6tt` int DEFAULT NULL,
  `n7tt` int DEFAULT NULL,
  `n8tt` int DEFAULT NULL,
  `pr1t` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pr2t` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pr3t` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prFin` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promTt` float DEFAULT NULL,
  PRIMARY KEY (`idCalificaciones`),
  KEY `calificacionesalumno_idx` (`legajo`),
  KEY `calificacionesmateria_idx` (`idMateria`),
  CONSTRAINT `calificacionesalumno` FOREIGN KEY (`legajo`) REFERENCES `alumno` (`idAlumno`),
  CONSTRAINT `calificacionesmateria` FOREIGN KEY (`idMateria`) REFERENCES `materia` (`idMateria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.calificacionesterciario
CREATE TABLE IF NOT EXISTS `calificacionesterciario` (
  `idCalificacion` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int NOT NULL,
  `idMateria` int NOT NULL,
  `n1` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n2` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n3` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n4` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n5` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n6` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n7` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n8` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f1` date DEFAULT NULL,
  `f2` date DEFAULT NULL,
  `f3` date DEFAULT NULL,
  `f4` date DEFAULT NULL,
  `f5` date DEFAULT NULL,
  `f6` date DEFAULT NULL,
  `f7` date DEFAULT NULL,
  `f8` date DEFAULT NULL,
  `r1` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r2` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r3` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r4` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r5` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r6` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r7` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r8` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asistencia` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sinAsistencia` int DEFAULT NULL,
  `examenIntegrador` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coloquio` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tmp_apenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  `estadoCursadoNumero` int DEFAULT NULL,
  `estadoCursado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `materiaAprobada` tinyint(1) DEFAULT NULL,
  `idInscripcionExamen` int DEFAULT NULL,
  PRIMARY KEY (`idCalificacion`),
  KEY `alumnoterc_idx` (`idAlumno`),
  KEY `materiaterc_idx` (`idMateria`),
  CONSTRAINT `alumnoterc` FOREIGN KEY (`idAlumno`) REFERENCES `alumnosterciario` (`idAlumno`),
  CONSTRAINT `materiaterc` FOREIGN KEY (`idMateria`) REFERENCES `materiaterciario` (`idMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=4942 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.certificacion
CREATE TABLE IF NOT EXISTS `certificacion` (
  `idcertificacion` int NOT NULL AUTO_INCREMENT,
  `idpersonal` int NOT NULL,
  `cargo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titularSuplente` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nroResolucion` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaAlta` date DEFAULT NULL,
  `FechaBaja` date DEFAULT NULL,
  `hsCatedra` int DEFAULT NULL,
  PRIMARY KEY (`idcertificacion`),
  KEY `idpersonal_idx` (`idpersonal`),
  CONSTRAINT `idpersonal` FOREIGN KEY (`idpersonal`) REFERENCES `personal` (`legajo`)
) ENGINE=InnoDB AUTO_INCREMENT=711 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.ciclolectivo
CREATE TABLE IF NOT EXISTS `ciclolectivo` (
  `idciclolectivo` int NOT NULL AUTO_INCREMENT,
  `anio` int DEFAULT NULL,
  PRIMARY KEY (`idciclolectivo`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.colegio
CREATE TABLE IF NOT EXISTS `colegio` (
  `nombreColegio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numCuenta` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localidad` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repLegal` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nivel` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codcol` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anioautoweb` int DEFAULT NULL,
  `anio_carga_notas` int DEFAULT NULL,
  `razonSocial` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domicilioComercial` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condicionIva` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `puntoVenta` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ingresosBrutos` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inicioActividades` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iDturnoautoweb` int DEFAULT NULL,
  `inscExamDesde` datetime DEFAULT NULL,
  `inscExamHasta` datetime DEFAULT NULL,
  `inscCursDesde` datetime DEFAULT NULL,
  `inscCursHasta` datetime DEFAULT NULL,
  `inscExamLectDesde` datetime DEFAULT NULL,
  `inscCursLectDesde` datetime DEFAULT NULL,
  `cargaActaVolDesde` datetime DEFAULT NULL,
  `cargaActaVolHasta` datetime DEFAULT NULL,
  `cargaActaVolTurno` int DEFAULT NULL,
  `codnivel` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passLegajoWeb` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licencia` mediumtext COLLATE utf8mb4_unicode_ci,
  `docenteModifica` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.condicion
CREATE TABLE IF NOT EXISTS `condicion` (
  `idCondicion` int NOT NULL AUTO_INCREMENT,
  `condicion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idCondicion`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.condicionescursado
CREATE TABLE IF NOT EXISTS `condicionescursado` (
  `idCondicion` int DEFAULT NULL,
  `condicion` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.consultas
CREATE TABLE IF NOT EXISTS `consultas` (
  `idConsulta` int NOT NULL AUTO_INCREMENT,
  `consultas` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`idConsulta`)
) ENGINE=InnoDB AUTO_INCREMENT=441 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.correlatividades
CREATE TABLE IF NOT EXISTS `correlatividades` (
  `idCorrelatividad` int NOT NULL AUTO_INCREMENT,
  `idCarrera` int NOT NULL,
  `nombreMateria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombreMateriaCorrelativa` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idCicloLectivo` int NOT NULL,
  PRIMARY KEY (`idCorrelatividad`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.correlatividadesterciario
CREATE TABLE IF NOT EXISTS `correlatividadesterciario` (
  `idcorrelatividades` int NOT NULL AUTO_INCREMENT,
  `idUnicoMateria` int NOT NULL,
  `idUnicoMatCorrelativa` int NOT NULL,
  `condicionCorrelatividad` int NOT NULL,
  `tipoInscripcion` int NOT NULL,
  `grupal` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idcorrelatividades`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.correo
CREATE TABLE IF NOT EXISTS `correo` (
  `cuenta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hostSMTP` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portSMTP` int DEFAULT NULL,
  `hostPOP` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portPOP` int DEFAULT NULL,
  `cuentaAutenticar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habilitarSSL` tinyint(1) DEFAULT NULL,
  `habilitarPOP` tinyint(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.creditos
CREATE TABLE IF NOT EXISTS `creditos` (
  `idCredito` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int DEFAULT NULL,
  `idPlan` int DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `trayecto` text,
  `cantidad` text,
  PRIMARY KEY (`idCredito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cuotas
CREATE TABLE IF NOT EXISTS `cuotas` (
  `idCuota` int NOT NULL AUTO_INCREMENT,
  `idCicloLectivo` int NOT NULL,
  `nroReferencia` int DEFAULT NULL,
  `concepto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importe` double(15,2) DEFAULT NULL,
  `vencimiento1` date DEFAULT NULL,
  `vencimiento2` date DEFAULT NULL,
  `idCurso` int DEFAULT NULL,
  `porcBeca` int DEFAULT NULL,
  PRIMARY KEY (`idCuota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cuotasgeneradas
CREATE TABLE IF NOT EXISTS `cuotasgeneradas` (
  `idCuotaGenerada` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int NOT NULL,
  `idNivel` int DEFAULT NULL,
  `idCuota` int NOT NULL,
  `idCicloLectivo` int DEFAULT NULL,
  `idCurso` int DEFAULT NULL,
  `nroReferencia` int DEFAULT NULL,
  `concepto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importePagado` decimal(10,2) NOT NULL,
  `importe` double(15,2) DEFAULT NULL,
  `vencimiento1` date DEFAULT NULL,
  `vencimiento2` date DEFAULT NULL,
  `porcBeca` int DEFAULT NULL,
  `porcBecaAlumno` int DEFAULT NULL,
  `ajuste` int DEFAULT NULL,
  PRIMARY KEY (`idCuotaGenerada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cupones
CREATE TABLE IF NOT EXISTS `cupones` (
  `idCupon` int NOT NULL AUTO_INCREMENT,
  `idCuotaGenerada` int DEFAULT NULL,
  `metodo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importePagado` double(15,2) DEFAULT NULL,
  `interesPagado` double(15,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  PRIMARY KEY (`idCupon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cursadofinalizado
CREATE TABLE IF NOT EXISTS `cursadofinalizado` (
  `idAlumno` int DEFAULT NULL,
  `idPlan` int DEFAULT NULL,
  `idCicloLectivo` int DEFAULT NULL,
  `intencionExamen` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.curso
CREATE TABLE IF NOT EXISTS `curso` (
  `idCurso` int NOT NULL AUTO_INCREMENT,
  `idNivel` int DEFAULT NULL,
  `nombre` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idciclo` int DEFAULT NULL,
  `idPlanEstudio` int NOT NULL,
  `idTurnoCurso` int DEFAULT NULL,
  `cursoPrincipal` int DEFAULT NULL,
  `idcursopredeterminado` int DEFAULT NULL,
  `idDivision` int DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idCurso`),
  KEY `cursonivel_idx` (`idNivel`),
  KEY `cursoAnio_idx` (`idciclo`),
  KEY `idcurpredeterminado_idx` (`idcursopredeterminado`),
  CONSTRAINT `cursoAnio` FOREIGN KEY (`idciclo`) REFERENCES `ciclolectivo` (`idciclolectivo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cursonivel` FOREIGN KEY (`idNivel`) REFERENCES `nivel` (`idNivel`),
  CONSTRAINT `idcurpredeterminado` FOREIGN KEY (`idcursopredeterminado`) REFERENCES `cursospredeterminado` (`idcursopredeterminado`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cursospredeterminado
CREATE TABLE IF NOT EXISTS `cursospredeterminado` (
  `idcursopredeterminado` int NOT NULL AUTO_INCREMENT,
  `idNivel` int NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idcursopredeterminado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cursossecuencia
CREATE TABLE IF NOT EXISTS `cursossecuencia` (
  `idCursoSecuencia` int NOT NULL AUTO_INCREMENT,
  `NombreCursoActual` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NombreCursoDestino` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idCursoSecuencia`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.cursoxmateria
CREATE TABLE IF NOT EXISTS `cursoxmateria` (
  `idCursoxMateria` int NOT NULL AUTO_INCREMENT,
  `idCurso` int NOT NULL,
  `idMateria` int NOT NULL,
  `anio` int NOT NULL,
  PRIMARY KEY (`idCursoxMateria`),
  KEY `cursoxmateriacurso_idx` (`idCurso`),
  KEY `cursoxmateriamateria_idx` (`idMateria`),
  CONSTRAINT `cursoxmateriacurso` FOREIGN KEY (`idCurso`) REFERENCES `curso` (`idCurso`),
  CONSTRAINT `cursoxmateriamateria` FOREIGN KEY (`idMateria`) REFERENCES `materia` (`idMateria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.descripcioncalificaciones
CREATE TABLE IF NOT EXISTS `descripcioncalificaciones` (
  `idMateria` int NOT NULL,
  `n1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n3` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n4` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n5` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n6` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n7` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n8` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r3` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r4` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r5` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r6` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r7` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `r8` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fn1` date DEFAULT NULL,
  `fn2` date DEFAULT NULL,
  `fn3` date DEFAULT NULL,
  `fn4` date DEFAULT NULL,
  `fn5` date DEFAULT NULL,
  `fn6` date DEFAULT NULL,
  `fn7` date DEFAULT NULL,
  `fn8` date DEFAULT NULL,
  `fr1` date DEFAULT NULL,
  `fr2` date DEFAULT NULL,
  `fr3` date DEFAULT NULL,
  `fr4` date DEFAULT NULL,
  `fr5` date DEFAULT NULL,
  `fr6` date DEFAULT NULL,
  `fr7` date DEFAULT NULL,
  `fr8` date DEFAULT NULL,
  `examenIntegrador` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idMateria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.division
CREATE TABLE IF NOT EXISTS `division` (
  `idDivision` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idDivision`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.documentacionalumnos
CREATE TABLE IF NOT EXISTS `documentacionalumnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `documento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idAlumno` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipoArchivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.familia
CREATE TABLE IF NOT EXISTS `familia` (
  `idFamilia` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idFamilia`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.fechascalendario
CREATE TABLE IF NOT EXISTS `fechascalendario` (
  `idFechaCalendario` int NOT NULL AUTO_INCREMENT,
  `idMateria` int DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `observaciones` varchar(100) DEFAULT NULL,
  `horario` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idFechaCalendario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.fechasexamenes
CREATE TABLE IF NOT EXISTS `fechasexamenes` (
  `idFechaExamen` int NOT NULL AUTO_INCREMENT,
  `idMateria` int NOT NULL,
  `idTurno` int NOT NULL,
  `idCicloLectivo` int NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `p1` int DEFAULT NULL,
  `p2` int DEFAULT NULL,
  `p3` int DEFAULT NULL,
  `p4` int DEFAULT NULL,
  `p5` int DEFAULT NULL,
  `p6` int DEFAULT NULL,
  `p7` int DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idFechaExamen`)
) ENGINE=InnoDB AUTO_INCREMENT=591 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.formularios
CREATE TABLE IF NOT EXISTS `formularios` (
  `idformulario` int NOT NULL,
  `formulario` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idformulario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.habilitartrigger
CREATE TABLE IF NOT EXISTS `habilitartrigger` (
  `habilitar` int DEFAULT NULL,
  `cierreok` int DEFAULT NULL,
  `fechaSincroActiva` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.historialcarreras
CREATE TABLE IF NOT EXISTS `historialcarreras` (
  `idHistorialCarrera` int NOT NULL AUTO_INCREMENT,
  `idPlan` int DEFAULT NULL,
  `idAlumno` int DEFAULT NULL,
  `idCiclolectivo` int DEFAULT NULL,
  `libro` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idHistorialCarrera`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.horarios
CREATE TABLE IF NOT EXISTS `horarios` (
  `modulo` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horarioInicio` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horarioFin` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.horariosmaterias
CREATE TABLE IF NOT EXISTS `horariosmaterias` (
  `modulo` int DEFAULT NULL,
  `lunes` tinyint(1) DEFAULT NULL,
  `martes` tinyint(1) DEFAULT NULL,
  `miercoles` tinyint(1) DEFAULT NULL,
  `jueves` tinyint(1) DEFAULT NULL,
  `viernes` tinyint(1) DEFAULT NULL,
  `sabado` tinyint(1) DEFAULT NULL,
  `idMateria` int NOT NULL,
  `idCurso` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.importexcurso
CREATE TABLE IF NOT EXISTS `importexcurso` (
  `idCurso` int NOT NULL,
  `importe` double(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.informemorosos
CREATE TABLE IF NOT EXISTS `informemorosos` (
  `encabezado` mediumtext COLLATE utf8mb4_unicode_ci,
  `cuerpo` mediumtext COLLATE utf8mb4_unicode_ci,
  `cuerpoBecados` mediumtext COLLATE utf8mb4_unicode_ci,
  `asunto` mediumtext COLLATE utf8mb4_unicode_ci,
  `archivo` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.inscripcionexamenes
CREATE TABLE IF NOT EXISTS `inscripcionexamenes` (
  `idInscripcion` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int NOT NULL,
  `idMateria` int NOT NULL,
  `idCicloLectivo` int NOT NULL,
  `idFechaExamen` int NOT NULL,
  `oral` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escrito` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calificacion` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libro` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idCondicion` int NOT NULL,
  `observaciones` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idInscripcion`)
) ENGINE=InnoDB AUTO_INCREMENT=2002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.inscripcionexamenes_web
CREATE TABLE IF NOT EXISTS `inscripcionexamenes_web` (
  `id_Inscripcion_web` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int DEFAULT NULL,
  `idMateria` int DEFAULT NULL,
  `idCicloLectivo` int DEFAULT NULL,
  `idFechaExamen` int DEFAULT NULL,
  `idCondicion` int DEFAULT NULL,
  `estado` int DEFAULT '-1',
  `observaciones` mediumtext COLLATE utf8mb4_unicode_ci,
  `fechhora_inscri` datetime DEFAULT NULL,
  `fechhora_proces` datetime DEFAULT NULL,
  PRIMARY KEY (`id_Inscripcion_web`),
  KEY `idMateria` (`idMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.licencias
CREATE TABLE IF NOT EXISTS `licencias` (
  `idlicencias` int NOT NULL AUTO_INCREMENT,
  `idPersonal` int NOT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaFin` date DEFAULT NULL,
  `parcial` int DEFAULT NULL,
  PRIMARY KEY (`idlicencias`),
  KEY `personal_idx` (`idPersonal`),
  CONSTRAINT `personal` FOREIGN KEY (`idPersonal`) REFERENCES `personal` (`legajo`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.llaves
CREATE TABLE IF NOT EXISTS `llaves` (
  `idllave` int NOT NULL,
  `llave` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idformulario` int NOT NULL,
  PRIMARY KEY (`idllave`),
  KEY `formid_idx` (`idformulario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.llavesgenerales
CREATE TABLE IF NOT EXISTS `llavesgenerales` (
  `idllavegral` int NOT NULL,
  `llavesgenerales` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idllavegral`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.llavesxform
CREATE TABLE IF NOT EXISTS `llavesxform` (
  `idusuario` int NOT NULL,
  `idformulario` int NOT NULL,
  `idllave` int DEFAULT NULL,
  `idllavegral` int NOT NULL,
  KEY `llaveid_idx` (`idllave`),
  KEY `formid_idx` (`idformulario`),
  KEY `usuarioid_idx` (`idusuario`),
  KEY `llavegralid_idx` (`idllavegral`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.log
CREATE TABLE IF NOT EXISTS `log` (
  `idlog` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime DEFAULT NULL,
  `idAlumno` int DEFAULT NULL,
  `idMateria` int DEFAULT NULL,
  `idMatriculacion` int DEFAULT NULL,
  `idMatriculacionMateria` int DEFAULT NULL,
  `usuario` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idlog`)
) ENGINE=InnoDB AUTO_INCREMENT=6404 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.materia
CREATE TABLE IF NOT EXISTS `materia` (
  `idMateria` int NOT NULL AUTO_INCREMENT,
  `orden` int NOT NULL,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idPlan` int NOT NULL,
  `idCiclo` int NOT NULL,
  `idCurso` int NOT NULL,
  `idNivel` int NOT NULL,
  `idPersonaTitular` int DEFAULT NULL,
  `idPersonaSuplente` int DEFAULT NULL,
  `idPersonaOpCarga` int DEFAULT NULL,
  `idCalculo` int NOT NULL,
  PRIMARY KEY (`idMateria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.materiaterciario
CREATE TABLE IF NOT EXISTS `materiaterciario` (
  `idMateria` int NOT NULL AUTO_INCREMENT,
  `idUnicoMateria` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idPlan` int NOT NULL,
  `idCicloLectivo` int NOT NULL,
  `idCurso` int NOT NULL,
  `ubicacion` int DEFAULT NULL,
  `idTipoMateria` int DEFAULT NULL,
  `horasCatedra` int DEFAULT NULL,
  `idtipocursado` int DEFAULT NULL,
  `calificacionRegular` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calificacionPromocion` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calificacionTrabajo` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asistenciaRegular` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asistenciaPromocion` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calificacionExamen` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asistenciaRegularRed` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asistenciaPromocionRed` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaFin` date DEFAULT NULL,
  `cantidadTurnosLibre` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidadTurnosRegular` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hsSinAp` int DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idMateria`),
  KEY `tipocursado_idx` (`idtipocursado`)
) ENGINE=InnoDB AUTO_INCREMENT=316 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.matriculacion
CREATE TABLE IF NOT EXISTS `matriculacion` (
  `idMatriculacion` int NOT NULL AUTO_INCREMENT,
  `idNivel` int DEFAULT NULL,
  `idCurso` int DEFAULT NULL,
  `idAlumno` int DEFAULT NULL,
  `fechaMatriculacion` date DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `estado` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarde` int DEFAULT NULL,
  `idPlanDeEstudio` int DEFAULT NULL,
  `pagoMatricula` int DEFAULT NULL,
  `pagoMonto` mediumtext COLLATE utf8mb4_unicode_ci,
  `certificadoSalud` int DEFAULT NULL,
  `fechaBajaMatriculacion` date DEFAULT NULL,
  `certificadoTrabajo` int DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  `contrato` int DEFAULT NULL,
  PRIMARY KEY (`idMatriculacion`),
  KEY `matriculacioncurso_idx` (`idCurso`),
  KEY `matriculacionnivel_idx` (`idNivel`),
  KEY `anio` (`anio`),
  CONSTRAINT `matriculacioncurso` FOREIGN KEY (`idCurso`) REFERENCES `curso` (`idCurso`),
  CONSTRAINT `matriculacionnivel` FOREIGN KEY (`idNivel`) REFERENCES `nivel` (`idNivel`)
) ENGINE=InnoDB AUTO_INCREMENT=448 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.matriculacionmateria
CREATE TABLE IF NOT EXISTS `matriculacionmateria` (
  `idMatriculacionMateria` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int NOT NULL,
  `idNivel` int NOT NULL,
  `idMateria` int NOT NULL,
  `fechaMatriculacion` date NOT NULL,
  `fechaBajaMatriculacion` date DEFAULT NULL,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idCicloLectivo` int NOT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idMatriculacionMateria`),
  KEY `alumid_idx` (`idAlumno`),
  CONSTRAINT `alumid` FOREIGN KEY (`idAlumno`) REFERENCES `alumnosterciario` (`idAlumno`)
) ENGINE=InnoDB AUTO_INCREMENT=4942 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.matriculacionmateria_web
CREATE TABLE IF NOT EXISTS `matriculacionmateria_web` (
  `id_matriculacion_web` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int DEFAULT NULL,
  `idMateria` int DEFAULT NULL,
  `idCicloLectivo` int DEFAULT NULL,
  `condicion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` int DEFAULT '-1',
  `observaciones` mediumtext COLLATE utf8mb4_unicode_ci,
  `fechhora_inscri` datetime DEFAULT NULL,
  `fechhora_proces` datetime DEFAULT NULL,
  PRIMARY KEY (`id_matriculacion_web`),
  KEY `idMateria` (`idMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.nivel
CREATE TABLE IF NOT EXISTS `nivel` (
  `idNivel` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idNivel`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.passwords
CREATE TABLE IF NOT EXISTS `passwords` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idPersona` int DEFAULT NULL,
  `legajo` int NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pp_user` int DEFAULT NULL,
  `webparam` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_passwords_personal` (`legajo`),
  CONSTRAINT `FK_passwords_personal` FOREIGN KEY (`legajo`) REFERENCES `personal` (`legajo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.passwords_alumnos
CREATE TABLE IF NOT EXISTS `passwords_alumnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idAlumno` int DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pp_user` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3938 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.persona
CREATE TABLE IF NOT EXISTS `persona` (
  `idPersona` int NOT NULL AUTO_INCREMENT,
  `apellido` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FotoCarnet` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaNac` date DEFAULT NULL,
  `nacionalidad` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lugarNac` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barrio` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuilPre` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuilPost` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefonoEmergencia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigoPostal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idPersona`),
  KEY `apellido` (`apellido`),
  KEY `nombre` (`nombre`),
  KEY `dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=603 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.personal
CREATE TABLE IF NOT EXISTS `personal` (
  `legajo` int NOT NULL AUTO_INCREMENT,
  `idPersona` int NOT NULL,
  `estadoCivil` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipoCargo` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legJunta` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legEscuela` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escalafD` date DEFAULT NULL,
  `escalafE` date DEFAULT NULL,
  `numReg` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apto` date DEFAULT NULL,
  `certArt28` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incapac` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actual` int DEFAULT NULL,
  `nivel` int DEFAULT NULL,
  `fechaBaja` date DEFAULT NULL,
  `tipoTitulo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailInst` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`legajo`),
  KEY `personalpersona_idx` (`idPersona`),
  CONSTRAINT `personalpersona` FOREIGN KEY (`idPersona`) REFERENCES `persona` (`idPersona`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.planabreviado
CREATE TABLE IF NOT EXISTS `planabreviado` (
  `idplanabreviado` int NOT NULL AUTO_INCREMENT,
  `idplan` int DEFAULT NULL,
  `abreviatura` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idplanabreviado`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.plandeestudio
CREATE TABLE IF NOT EXISTS `plandeestudio` (
  `idPlan` int NOT NULL AUTO_INCREMENT,
  `idNivel` int NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cursado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolucion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idPlan`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.planpagos
CREATE TABLE IF NOT EXISTS `planpagos` (
  `idPlanPagos` int NOT NULL AUTO_INCREMENT,
  `cantidadCuotas` int DEFAULT NULL,
  `importeTotal` decimal(10,2) DEFAULT NULL,
  `cuotasSeleccionadas` varchar(100) DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaFin` datetime DEFAULT NULL,
  PRIMARY KEY (`idPlanPagos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.profesorxmateria
CREATE TABLE IF NOT EXISTS `profesorxmateria` (
  `idProfXMat` int NOT NULL AUTO_INCREMENT,
  `idMateria` int NOT NULL,
  `idPersonal` int NOT NULL,
  `tipo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `registroModificacion` tinyint(1) DEFAULT '0',
  `registroNuevo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`idProfXMat`)
) ENGINE=InnoDB AUTO_INCREMENT=581 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.referenciacuotas
CREATE TABLE IF NOT EXISTS `referenciacuotas` (
  `nroReferencia` int NOT NULL AUTO_INCREMENT,
  `concepto` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`nroReferencia`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.resoluciones
CREATE TABLE IF NOT EXISTS `resoluciones` (
  `idResolucion` int NOT NULL AUTO_INCREMENT,
  `tipoResolucion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `procedencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idReferencia` int DEFAULT NULL,
  PRIMARY KEY (`idResolucion`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.respaldo
CREATE TABLE IF NOT EXISTS `respaldo` (
  `idrespaldo` int NOT NULL AUTO_INCREMENT,
  `fecharespaldo` date DEFAULT NULL,
  `pc` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idrespaldo`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.tipoasistencia
CREATE TABLE IF NOT EXISTS `tipoasistencia` (
  `idTipo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idTipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.tipocursadoxmateria
CREATE TABLE IF NOT EXISTS `tipocursadoxmateria` (
  `idtipocursadoxmateria` int NOT NULL AUTO_INCREMENT,
  `tipocursado` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idcursopredeterminado` int DEFAULT NULL,
  `cursado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idtipocursadoxmateria`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.tipomateria
CREATE TABLE IF NOT EXISTS `tipomateria` (
  `idTipoMateria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idTipoMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.turnoscursos
CREATE TABLE IF NOT EXISTS `turnoscursos` (
  `idTurnoCurso` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idTurnoCurso`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.turnosexamenes
CREATE TABLE IF NOT EXISTS `turnosexamenes` (
  `idTurno` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idTurno`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idusuarios` int NOT NULL AUTO_INCREMENT,
  `nombreUsuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipoPermiso` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idnivel` int DEFAULT NULL,
  PRIMARY KEY (`idusuarios`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla sesystem_prueba.usuariospredeterminado
CREATE TABLE IF NOT EXISTS `usuariospredeterminado` (
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idformulario` int NOT NULL,
  `idllavegral` int NOT NULL,
  `idllave` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
