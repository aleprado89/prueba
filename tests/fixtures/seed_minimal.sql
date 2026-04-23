-- Datos mínimos para pruebas de integración (después de importar shema_sistemasescolares.sql).
-- Ciclo lectivo id fijo para asserts predecibles.
SET NAMES utf8mb4;

INSERT INTO ciclolectivo (idciclolectivo, anio) VALUES (1, 2026)
  ON DUPLICATE KEY UPDATE anio = VALUES(anio);

INSERT INTO colegio (nombreColegio, nivel, codnivel, docenteModifica, anioautoweb)
VALUES ('Instituto de Prueba SE', 'Terciario', '6', 0, 2026);
